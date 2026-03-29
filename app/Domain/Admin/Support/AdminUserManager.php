<?php

namespace App\Domain\Admin\Support;

use App\Domain\Admin\Models\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserManager
{
    public function __construct(
        protected AdminPermissionCatalog $permissionCatalog,
    ) {
    }

    public function permissionOptions()
    {
        return $this->permissionCatalog->options();
    }

    public function upsertUser(?User $user, array $payload): User
    {
        $user ??= new User();

        return DB::transaction(function () use ($user, $payload): User {
            $fullName = trim(((string) ($payload['first_name'] ?? '')).' '.((string) ($payload['last_name'] ?? '')));

            $user->fill([
                'username' => $payload['username'],
                'first_name' => $payload['first_name'] ?? '',
                'last_name' => $payload['last_name'] ?? '',
                'name' => $fullName !== '' ? $fullName : $payload['username'],
                'email' => $payload['email'],
                'is_active' => $payload['is_active'],
            ]);

            if (! empty($payload['password'])) {
                $user->password = Hash::make((string) $payload['password']);
            }

            $user->save();

            $roles = Role::query()->whereIn('id', $payload['role_ids'] ?? [])->get();
            $user->syncRoles($roles);

            UserProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['sucursal_id' => $payload['sucursal_id'] ?? null],
            );

            return $user->load('panelProfile.sucursal', 'roles');
        });
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
        $user->save();
    }
}
