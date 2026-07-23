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
            'categories',
            'tags',
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
                Permission::findOrCreate("{$resource}.{$action}");
            }
        }

        Permission::findOrCreate('posts.deleteOwn');
        Permission::findOrCreate('access-admin');
        Permission::findOrCreate('admin.use-page-code-mode');
        Permission::findOrCreate('admin.access-system');
        Permission::findOrCreate('admin.access-appearance');

        // Wajib setelah create permission jika model events dimute
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::pluck('name')->toArray();

        // Admin: semua
        $admin = Role::findOrCreate(UserRole::Admin->value);
        $admin->syncPermissions($allPermissions);

        // Editor: akses admin + konten/halaman/media/interaksi (tanpa Tampilan, tanpa Sistem)
        $editor = Role::findOrCreate(UserRole::Editor->value);
        $editor->syncPermissions(array_merge(
            ['access-admin', 'ai.create', 'ai.update'],
            $this->permissionNamesFor([
                'posts',
                'pages',
                'media',
                'categories',
                'tags',
                'contact-messages',
                'testimonials',
                'ratings',
                'galleries',
            ]),
        ));

        // Author: akses admin + posts milik sendiri + media
        $author = Role::findOrCreate(UserRole::Author->value);
        $author->syncPermissions(array_merge(
            ['access-admin'],
            $this->permissionNamesFor(['media']),
            ['posts.viewAny', 'posts.create', 'posts.update', 'posts.deleteOwn'],
        ));

        // C4: reset cache Spatie di akhir agar role/permission yang baru
        // di-sync langsung terbaca oleh request/test berikutnya.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
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
