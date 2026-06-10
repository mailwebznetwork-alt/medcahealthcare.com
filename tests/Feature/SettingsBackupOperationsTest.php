<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\BackupOperator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

/**
 * @return array<string, bool>
 */
function settingsBackupAllModulesOn(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

function backupOperatorUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsBackupAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'MOMJERRIE',
    ]);
}

it('forbids run backup post for super admins who are not backup operators', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsBackupAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'Not Operator',
    ]);

    $this->actingAs($user)
        ->post(route('settings.system.backup'))
        ->assertForbidden();
});

it('forbids backup download for super admins who are not backup operators', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsBackupAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'Not Operator',
    ]);

    $this->actingAs($user)
        ->get(route('settings.system.backup.download'))
        ->assertForbidden();
});

it('redirects backup download when sqlite is not file backed', function () {
    $this->actingAs(backupOperatorUser())
        ->get(route('settings.system.backup.download'))
        ->assertRedirect(route('settings.backup'))
        ->assertSessionHasErrors('integration');
});

it('forbids backup restore for super admins who are not backup operators', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => settingsBackupAllModulesOn(),
        'role' => 'super_admin',
        'name' => 'Not Operator',
    ]);

    $this->actingAs($user)
        ->post(route('settings.system.backup.restore'), [])
        ->assertForbidden();
});

it('redirects restore when archive is not a valid full-site backup zip', function () {
    $realSqlite = sys_get_temp_dir().'/mom-restore-'.uniqid('', true).'.sqlite';
    $pdo = new PDO('sqlite:'.$realSqlite);
    $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY);');
    $pdo = null;

    $upload = new UploadedFile($realSqlite, 'backup.zip', 'application/zip', null, true);

    try {
        $this->actingAs(backupOperatorUser())
            ->post(route('settings.system.backup.restore'), [
                'backup_file' => $upload,
            ])
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHasErrors('integration');
    } finally {
        if (file_exists($realSqlite)) {
            unlink($realSqlite);
        }
    }
});

describe('BackupOperator', function () {
    beforeEach(function (): void {
        Config::set('settings.backup_operator_names', ['momjerrie', 'ops']);
    });

    it('allows super_admin when normalized name matches', function (): void {
        $user = User::factory()->make([
            'name' => 'MOMJERRIE',
            'role' => 'super_admin',
        ]);

        expect(BackupOperator::allows($user))->toBeTrue();
    });

    it('denies super_admin when name does not match allow list', function (): void {
        $user = User::factory()->make([
            'name' => 'Someone Else',
            'role' => 'super_admin',
        ]);

        expect(BackupOperator::allows($user))->toBeFalse();
    });

    it('denies non-super_admin even when name matches', function (): void {
        $user = User::factory()->make([
            'name' => 'MOMJERRIE',
            'role' => 'admin',
        ]);

        expect(BackupOperator::allows($user))->toBeFalse();
    });
});
