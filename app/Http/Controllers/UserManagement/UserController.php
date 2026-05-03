<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\Support\RootAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
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
        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role_label' => $request->validated('role_label'),
            'module_access' => $request->normalizedModuleAccess(),
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->password = Hash::make($request->validated('password'));

        if ($request->hasFile('profile_image')) {
            $user->profile_image_path = $request->file('profile_image')->store('profile-images', 'public');
        }

        $user->save();

        return redirect()->route('user-management.index')->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('user-management.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role_label' => $request->validated('role_label'),
            'module_access' => $request->normalizedModuleAccess($user),
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->validated('password'));
        }

        if ($request->boolean('remove_profile_image') && $user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
            $user->profile_image_path = null;
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $user->profile_image_path = $request->file('profile_image')->store('profile-images', 'public');
        }

        if (! RootAccount::isRootUser($user)) {
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();

        return redirect()->route('user-management.index')->with('status', 'user-updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->delete();

        return redirect()->route('user-management.index')->with('status', 'user-deleted');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('changeActiveState', $user);

        $user->update(['is_active' => true]);

        return redirect()->route('user-management.index')->with('status', 'user-activated');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('changeActiveState', $user);

        $user->update(['is_active' => false]);

        return redirect()->route('user-management.index')->with('status', 'user-deactivated');
    }
}
