<?php

namespace Tests\Feature\Migration;

use App\Domain\Admin\Support\AdminPermissionCatalog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyAccessSyncWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_access_sync_creates_roles_and_assigns_users(): void
    {
        $sourcePath = storage_path('framework/testing/legacy-access-'.Str::uuid().'.sqlite');

        try {
            File::ensureDirectoryExists(dirname($sourcePath));
            touch($sourcePath);

            config([
                'database.connections.legacy_access_source' => [
                    'driver' => 'sqlite',
                    'database' => $sourcePath,
                    'prefix' => '',
                    'foreign_key_constraints' => false,
                ],
            ]);
            DB::purge('legacy_access_source');

            $this->createLegacyAccessSchema();
            $this->seedLegacyAccessData();

            User::query()->create([
                'id' => 1,
                'name' => 'Vendedor Legacy',
                'username' => 'vendedor',
                'email' => 'vendedor@example.com',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);
            User::query()->create([
                'id' => 2,
                'name' => 'Admin Legacy',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);

            $this->artisan('migracion:sincronizar-accesos', [
                '--connection' => 'legacy_access_source',
            ])->assertExitCode(0);

            $admin = User::query()->findOrFail(2);
            $seller = User::query()->findOrFail(1);

            $this->assertDatabaseHas('roles', ['name' => 'Administrador']);
            $this->assertDatabaseHas('roles', ['name' => 'Vendedor']);
            $this->assertTrue($admin->fresh()->hasRole('Administrador'));
            $this->assertTrue($seller->fresh()->hasRole('Vendedor'));
            $this->assertTrue($admin->can('admin_panel.manage_users'));
            $this->assertTrue($admin->can('catalogo.manage_catalogo'));
            $this->assertTrue($admin->can('admin_panel.view_reportes'));
            $this->assertTrue($seller->can('ventas.usar_caja_pos'));
            $this->assertTrue($seller->can('ventas.view_venta'));
            $this->assertFalse($seller->can('admin_panel.manage_users'));
        } finally {
            DB::purge('legacy_access_source');
            File::delete($sourcePath);
        }
    }

    protected function createLegacyAccessSchema(): void
    {
        Schema::connection('legacy_access_source')->create('auth_group', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        Schema::connection('legacy_access_source')->create('django_content_type', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('app_label');
            $table->string('model');
        });

        Schema::connection('legacy_access_source')->create('auth_permission', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('content_type_id');
            $table->string('codename');
        });

        Schema::connection('legacy_access_source')->create('auth_group_permissions', function (Blueprint $table): void {
            $table->integer('group_id');
            $table->integer('permission_id');
        });

        Schema::connection('legacy_access_source')->create('auth_user_groups', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('group_id');
        });

        Schema::connection('legacy_access_source')->create('auth_user_user_permissions', function (Blueprint $table): void {
            $table->integer('user_id');
            $table->integer('permission_id');
        });
    }

    protected function seedLegacyAccessData(): void
    {
        DB::connection('legacy_access_source')->table('auth_group')->insert([
            ['id' => 1, 'name' => 'Vendedor'],
            ['id' => 2, 'name' => 'Administrador'],
        ]);

        DB::connection('legacy_access_source')->table('django_content_type')->insert([
            ['id' => 1, 'app_label' => 'ventas', 'model' => 'venta'],
            ['id' => 2, 'app_label' => 'catalogo', 'model' => 'producto'],
            ['id' => 3, 'app_label' => 'admin_panel', 'model' => 'usuarioperfil'],
            ['id' => 4, 'app_label' => 'caja', 'model' => 'cajasesion'],
        ]);

        DB::connection('legacy_access_source')->table('auth_permission')->insert([
            ['id' => 1, 'content_type_id' => 1, 'codename' => 'usar_caja_pos'],
            ['id' => 2, 'content_type_id' => 1, 'codename' => 'view_venta'],
            ['id' => 3, 'content_type_id' => 2, 'codename' => 'change_producto'],
            ['id' => 4, 'content_type_id' => 3, 'codename' => 'change_usuarioperfil'],
            ['id' => 5, 'content_type_id' => 3, 'codename' => 'view_usuarioperfil'],
            ['id' => 6, 'content_type_id' => 4, 'codename' => 'view_cajasesion'],
        ]);

        DB::connection('legacy_access_source')->table('auth_group_permissions')->insert([
            ['group_id' => 1, 'permission_id' => 1],
            ['group_id' => 1, 'permission_id' => 2],
            ['group_id' => 1, 'permission_id' => 6],
            ['group_id' => 2, 'permission_id' => 1],
            ['group_id' => 2, 'permission_id' => 2],
            ['group_id' => 2, 'permission_id' => 3],
            ['group_id' => 2, 'permission_id' => 4],
            ['group_id' => 2, 'permission_id' => 5],
        ]);

        DB::connection('legacy_access_source')->table('auth_user_groups')->insert([
            ['user_id' => 1, 'group_id' => 1],
            ['user_id' => 2, 'group_id' => 2],
        ]);
    }
}
