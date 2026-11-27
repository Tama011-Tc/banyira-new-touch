<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\ContentSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Brings an existing installation up to date. Re-running the reference seeder
 * would reset role permissions that administrators have since changed, so the
 * new permissions are only added here.
 */
return new class extends Migration
{
    private const GRANTS = [
        'it_admin' => ['content.manage', 'content.publish', 'backups.manage'],
        'sales_marketing' => ['content.manage', 'content.publish'],
    ];

    public function up(): void
    {
        foreach (ReferenceDataSeeder::PERMISSIONS as $group => $items) {
            foreach (array_intersect_key($items, array_flip(array_merge(...array_values(self::GRANTS)))) as $slug => $name) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'group' => $group]);
            }
        }
        foreach (self::GRANTS as $role => $slugs) {
            Role::where('slug', $role)->first()?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $slugs)->pluck('id'));
        }

        (new ContentSeeder)->run();
    }

    public function down(): void
    {
        Permission::whereIn('slug', array_merge(...array_values(self::GRANTS)))->delete();
    }
};
