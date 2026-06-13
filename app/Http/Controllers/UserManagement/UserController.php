<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\ModuleAccess;
use App\Services\ActivityLogService;
use App\Services\Integrations\OutboundWebhookDispatcher;
use App\Services\Security\PasswordSecurityService;
use App\Support\RootAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly PasswordSecurityService $passwordSecurity,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->visibleInUserManagementDirectory()
            ->orderBy('name');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('role_label', 'like', $like);
            });
        }

        $status = (string) $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($role = trim((string) $request->query('role', ''))) {
            $query->where('role_label', $role);
        }

        /** @var LengthAwarePaginator<int, User> $users */
        $users = $query->paginate(15)->withQueryString();

        $roleLabels = User::query()
            ->visibleInUserManagementDirectory()
            ->whereNotNull('role_label')
            ->where('role_label', '!=', '')
            ->distinct()
            ->orderBy('role_label')
            ->pluck('role_label');

        return view('user-management.index', compact('users', 'roleLabels'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('user-management.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User;
        $role = $request->validated('role');

        $roleLabel = trim((string) $request->validated('role_label', ''));
        /** @var array<string, mixed> $modulePayload */
        $modulePayload = $request->input('module_access', []);

        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
        ]);
        $user->forceFill([
            'role' => $role,
            'role_label' => $roleLabel !== '' ? $roleLabel : $this->roleLabelFor($role),
            'email_verified_at' => now(),
            'is_active' => $request->boolean('is_active', true),
            'module_access' => $this->normalizedModuleAccessFromInput($modulePayload),
        ]);
        $plainPassword = (string) $request->validated('password');
        $user->password = $plainPassword;

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            if (! $file->isValid()) {
                $this->activityLogService->log(
                    'upload_validation_failure',
                    'user_management',
                    sprintf('Invalid upload while creating user from IP %s.', (string) $request->ip())
                );

                throw ValidationException::withMessages([
                    'profile_image' => __('Invalid uploaded file.'),
                ]);
            }

            $mimeType = (string) $file->getMimeType();
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            if (! in_array($mimeType, $allowedMimeTypes, true)) {
                $this->activityLogService->log(
                    'upload_validation_failure',
                    'user_management',
                    sprintf('Rejected mime type "%s" while creating user.', $mimeType)
                );

                throw ValidationException::withMessages([
                    'profile_image' => __('Invalid file mime type.'),
                ]);
            }

            $user->profile_image_path = $file->store('uploads', 'public');
        }

        $user->save();

        if (! Hash::check($plainPassword, $user->fresh()->password)) {
            $user->delete();

            $this->activityLogService->log(
                'password_change_failed',
                'security',
                sprintf('User create aborted: password hash mismatch for %s.', (string) $user->email)
            );

            throw ValidationException::withMessages([
                'password' => [__('The password could not be saved correctly. Please try again.')],
            ]);
        }

        app(OutboundWebhookDispatcher::class)->dispatch('user.registered', [
            'user_id' => $user->id,
            'registration' => 'admin_created',
            'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) ($user->role ?? ''),
        ]);

        $this->activityLogService->log(
            'user_create',
            'user_management',
            sprintf('Created user ID %d (%s).', $user->id, $user->email)
        );

        return redirect()->route('user-management.index')->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('user-management.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role_label' => $request->validated('role_label'),
        ]);
        $user->forceFill([
            'role' => $request->validated('role'),
        ]);

        $plainPassword = $request->filled('password') ? (string) $request->validated('password') : null;

        if ($plainPassword !== null) {
            $this->passwordSecurity->assertActorPassword($request);
            $user->password = $plainPassword;
        }

        if (! $user->isRootSuperAdmin()) {
            /** @var array<string, mixed> $modulePayload */
            $modulePayload = $request->input('module_access', []);
            $user->module_access = $this->normalizedModuleAccessFromInput($modulePayload);
        }

        if ($request->boolean('remove_profile_image') && $user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
            $user->profile_image_path = null;
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            if (! $file->isValid()) {
                $this->activityLogService->log(
                    'upload_validation_failure',
                    'user_management',
                    sprintf('Invalid upload while updating user ID %d.', $user->id)
                );

                throw ValidationException::withMessages([
                    'profile_image' => __('Invalid uploaded file.'),
                ]);
            }

            $mimeType = (string) $file->getMimeType();
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            if (! in_array($mimeType, $allowedMimeTypes, true)) {
                $this->activityLogService->log(
                    'upload_validation_failure',
                    'user_management',
                    sprintf('Rejected mime type "%s" while updating user ID %d.', $mimeType, $user->id)
                );

                throw ValidationException::withMessages([
                    'profile_image' => __('Invalid file mime type.'),
                ]);
            }

            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $user->profile_image_path = $file->store('uploads', 'public');
        }

        if (! RootAccount::isRootUser($user)) {
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();

        if ($plainPassword !== null) {
            $this->passwordSecurity->finalizePasswordChange(
                $user,
                $plainPassword,
                'user_management_admin_reset',
                (int) $request->user()->id,
            );
        }

        $this->activityLogService->log(
            'user_update',
            'user_management',
            sprintf('Updated user ID %d.', $user->id)
        );

        return redirect()->route('user-management.index')->with('status', 'user-updated');
    }

    /**
     * Build a full module_access map from checkbox POST input (unchecked keys omitted).
     *
     * @param  array<string, mixed>  $incoming
     * @return array<string, bool>
     */
    private function normalizedModuleAccessFromInput(array $incoming): array
    {
        $merged = [];

        foreach (ModuleAccess::keys() as $key) {
            $merged[$key] = isset($incoming[$key]) && filter_var($incoming[$key], FILTER_VALIDATE_BOOLEAN);
        }

        return $merged;
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $userId = $user->id;

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->delete();
        $this->activityLogService->log(
            'user_delete',
            'user_management',
            sprintf('Deleted user ID %d.', $userId)
        );

        return redirect()->route('user-management.index')->with('status', 'user-deleted');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('changeActiveState', $user);

        $user->forceFill(['is_active' => true])->save();

        return redirect()->route('user-management.index')->with('status', 'user-activated');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('changeActiveState', $user);

        $user->forceFill(['is_active' => false])->save();

        return redirect()->route('user-management.index')->with('status', 'user-deactivated');
    }

    private function roleLabelFor(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'editor' => 'Editor',
            default => 'Viewer',
        };
    }
}
