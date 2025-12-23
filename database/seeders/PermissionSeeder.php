<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. إنشاء الأدوار
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $adminRole = Role::create(['name' => 'admin']);

        // 2. إنشاء الصلاحيات الأساسية
        $permissions = [
            // المستخدمين
            'view users', 'create users', 'edit users', 'delete users',

            // الطلبات
            'view orders', 'create orders', 'edit orders', 'delete orders',

            // المنتجات
            'view products', 'create products', 'edit products', 'delete products',

            // الفئات
            'view categories', 'create categories', 'edit categories', 'delete categories',

            // للإعدادات (للسوبر أدمن فقط)
            'manage settings'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. منح الصلاحيات للأدوار
        // السوبر أدمن يحصل على كل الصلاحيات
        $superAdminRole->givePermissionTo(Permission::all());

        // الأدمن يحصل على صلاحيات محدودة
        $adminRole->givePermissionTo([
            'view users', 'edit users',
            'view orders', 'edit orders',
            'view products', 'edit products',
            'view categories', 'edit categories'
        ]);

        // 4. إنشاء مستخدم سوبر أدمن
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@bondok.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // 5. إنشاء مستخدم أدمن
        $admin = User::firstOrCreate(
            ['email' => 'admin@bondok.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('✅ تم إنشاء الأدوار والصلاحيات بنجاح!');
        $this->command->info('📧 Super Admin: super@bondok.com / superadmin123');
        $this->command->info('📧 Admin: admin@bondok.com / admin123');
    }
}
