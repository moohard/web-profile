<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permission dasar untuk seluruh resource CMS
        $resources = [
            'posts',
            'pages',
            'menus',
            'widgets',
            'media',
            'users',
            'settings',
            'ai',
            'content-types',
            'languages',
            'writing-styles',
            'rating-criteria',
            'contact-messages',
            'testimonials',
            'ratings',
            'galleries',
        ];
        $actions = ['viewAny', 'create', 'update', 'delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$resource}.{$action}"]);
            }
        }

        Permission::firstOrCreate(['name' => 'posts.deleteOwn']);
        Permission::firstOrCreate(['name' => 'access-admin']);
        Permission::firstOrCreate(['name' => 'admin.use-page-code-mode']);
        Permission::firstOrCreate(['name' => 'admin.access-system']);
        Permission::firstOrCreate(['name' => 'admin.access-appearance']);

        // Wajib setelah create permission jika model events dimute
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::pluck('name')->toArray();

        // Admin: semua
        $admin = Role::firstOrCreate(['name' => UserRole::Admin->value]);
        $admin->syncPermissions($allPermissions);

        // Editor: akses admin + konten/halaman/media/interaksi (tanpa Tampilan, tanpa Sistem)
        $editor = Role::firstOrCreate(['name' => UserRole::Editor->value]);
        $editor->syncPermissions(array_merge(
            ['access-admin'],
            $this->permissionNamesFor(['posts', 'pages', 'media', 'contact-messages', 'testimonials', 'ratings', 'galleries']),
        ));

        // Author: akses admin + posts milik sendiri + media
        $author = Role::firstOrCreate(['name' => UserRole::Author->value]);
        $author->syncPermissions(array_merge(
            ['access-admin'],
            $this->permissionNamesFor(['media']),
            ['posts.viewAny', 'posts.create', 'posts.update', 'posts.deleteOwn'],
        ));
    }

    /**
     * @param  list<string>  $resources
     * @return list<string>
     */
    private function permissionNamesFor(array $resources): array
    {
        $out = [];
        foreach ($resources as $resource) {
            foreach (['viewAny', 'create', 'update', 'delete'] as $action) {
                $out[] = "{$resource}.{$action}";
            }
        }

        return $out;
    }
}
