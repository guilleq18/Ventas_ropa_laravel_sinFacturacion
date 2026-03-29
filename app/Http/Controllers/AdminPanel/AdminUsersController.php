<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminRoleManager;
use App\Domain\Admin\Support\AdminUserManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRoleRequest;
use App\Http\Requests\Admin\AdminUserPasswordRequest;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUsersController extends Controller
{
    public function index(
        Request $request,
        AdminUserManager $userManager,
        AdminRoleManager $roleManager,
    ): View {
        $tab = in_array($request->query('tab'), ['usuarios', 'roles'], true)
            ? (string) $request->query('tab')
            : 'usuarios';

        return view('admin-panel.users.index', [
            'tab' => $tab,
            'users' => User::query()
                ->with(['panelProfile.sucursal', 'roles'])
                ->orderBy('username')
                ->orderBy('email')
                ->get(),
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'roleOptions' => Role::query()->orderBy('name')->get(['id', 'name']),
            'permissionOptions' => $roleManager->permissionOptions()->groupBy('group'),
            'branches' => Sucursal::query()->where('activa', true)->orderBy('nombre')->get(),
            'editUser' => $request->filled('edit_user')
                ? User::query()->with(['panelProfile', 'roles'])->find($request->integer('edit_user'))
                : null,
            'passwordUser' => $request->filled('change_password_user')
                ? User::query()->find($request->integer('change_password_user'))
                : null,
            'editRole' => $request->filled('edit_role')
                ? Role::query()->with('permissions')->find($request->integer('edit_role'))
                : null,
        ]);
    }

    public function storeUser(AdminUserRequest $request, AdminUserManager $userManager): RedirectResponse
    {
        $userManager->upsertUser(null, $request->validatedPayload());

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'usuarios'])
            ->with('success', 'Usuario creado correctamente.');
    }

    public function updateUser(
        AdminUserRequest $request,
        User $user,
        AdminUserManager $userManager,
    ): RedirectResponse {
        $userManager->upsertUser($user, $request->validatedPayload());

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'usuarios'])
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user) && $user->is_active) {
            return redirect()
                ->route('admin-panel.users.index', ['tab' => 'usuarios'])
                ->with('error', 'No podes desactivarte a vos mismo desde esta pantalla.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $state = $user->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'usuarios'])
            ->with('success', "Usuario {$user->username} {$state}.");
    }

    public function updatePassword(
        AdminUserPasswordRequest $request,
        User $user,
        AdminUserManager $userManager,
    ): RedirectResponse {
        $userManager->updatePassword($user, (string) $request->validated('password'));

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'usuarios'])
            ->with('success', "Contrasena actualizada para {$user->username}.");
    }

    public function storeRole(AdminRoleRequest $request, AdminRoleManager $roleManager): RedirectResponse
    {
        $roleManager->upsertRole(null, $request->validatedPayload());

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'roles'])
            ->with('success', 'Rol creado correctamente.');
    }

    public function updateRole(
        AdminRoleRequest $request,
        Role $role,
        AdminRoleManager $roleManager,
    ): RedirectResponse {
        $roleManager->upsertRole($role, $request->validatedPayload());

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'roles'])
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        if (User::role($role->name)->exists()) {
            return redirect()
                ->route('admin-panel.users.index', ['tab' => 'roles'])
                ->with('error', "No se puede eliminar el rol {$role->name}: tiene usuarios asignados.");
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()
            ->route('admin-panel.users.index', ['tab' => 'roles'])
            ->with('success', "Rol eliminado: {$roleName}.");
    }
}
