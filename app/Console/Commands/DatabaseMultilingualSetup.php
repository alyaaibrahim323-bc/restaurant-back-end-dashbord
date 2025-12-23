<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseMultilingualSetup extends Command
{
    protected $signature = 'db:multilingual-setup';
    protected $description = 'Add multilingual support to all tables and add image to product_option_values';

    public function handle()
    {
        $this->info('🚀 Starting multilingual database setup...');

        // 1. جدول Products
        $this->setupProductsTable();

        // 2. جدول Categories
        $this->setupCategoriesTable();

        // 3. جدول product_option_values
        $this->setupProductOptionValuesTable();

        // 4. جدول Orders
        $this->setupOrdersTable();

        // 5. جدول Offers
        $this->setupOffersTable();
        
        // 6. جدول product_options
        $this->setupProductOptionsTable();

        $this->info('✅ Multilingual setup completed successfully!');
    }

    private function setupProductsTable()
    {
        $this->info('📦 Setting up products table...');

        if (!Schema::hasTable('products')) {
            $this->error('Products table does not exist!');
            return;
        }

        $columns = [
            'name_en' => 'ALTER TABLE products ADD COLUMN name_en VARCHAR(255) AFTER name',
            'name_ar' => 'ALTER TABLE products ADD COLUMN name_ar VARCHAR(255) AFTER name_en',
            'description_en' => 'ALTER TABLE products ADD COLUMN description_en TEXT AFTER description',
            'description_ar' => 'ALTER TABLE products ADD COLUMN description_ar TEXT AFTER description_en',
            'slug_en' => 'ALTER TABLE products ADD COLUMN slug_en VARCHAR(255) AFTER slug',
            'slug_ar' => 'ALTER TABLE products ADD COLUMN slug_ar VARCHAR(255) AFTER slug_en',
        ];

        foreach ($columns as $column => $sql) {
            if (!Schema::hasColumn('products', $column)) {
                DB::statement($sql);
                $this->info("✅ Added {$column} to products table");
            } else {
                $this->warn("⚠️ {$column} already exists in products table");
            }
        }

        // جعل الحقول القديمة nullable
        $this->makeColumnsNullable('products', ['name', 'description', 'slug']);
        
        $this->info('✅ Products table multilingual setup completed');
    }

    private function setupCategoriesTable()
    {
        $this->info('📁 Setting up categories table...');

        if (!Schema::hasTable('categories')) {
            $this->error('Categories table does not exist!');
            return;
        }

        $columns = [
            'name_en' => 'ALTER TABLE categories ADD COLUMN name_en VARCHAR(255) AFTER name',
            'name_ar' => 'ALTER TABLE categories ADD COLUMN name_ar VARCHAR(255) AFTER name_en',
            'description_en' => 'ALTER TABLE categories ADD COLUMN description_en TEXT AFTER description',
            'description_ar' => 'ALTER TABLE categories ADD COLUMN description_ar TEXT AFTER description_en',
            'slug_en' => 'ALTER TABLE categories ADD COLUMN slug_en VARCHAR(255) AFTER slug',
            'slug_ar' => 'ALTER TABLE categories ADD COLUMN slug_ar VARCHAR(255) AFTER slug_en',
        ];

        foreach ($columns as $column => $sql) {
            if (!Schema::hasColumn('categories', $column)) {
                DB::statement($sql);
                $this->info("✅ Added {$column} to categories table");
            } else {
                $this->warn("⚠️ {$column} already exists in categories table");
            }
        }

        // جعل الحقول القديمة nullable
        $this->makeColumnsNullable('categories', ['name', 'description', 'slug']);
        
        $this->info('✅ Categories table multilingual setup completed');
    }

    private function setupProductOptionValuesTable()
    {
        $this->info('🎨 Setting up product_option_values table...');
    
        if (!Schema::hasTable('product_option_values')) {
            $this->warn('⚠️ product_option_values table does not exist, skipping...');
            return;
        }
    
        // إضافة الحقول الجديدة للقيم multilingual
        $columns = [
            'value_ar' => 'ALTER TABLE product_option_values ADD COLUMN value_ar VARCHAR(255) NULL AFTER value',
            'image' => 'ALTER TABLE product_option_values ADD COLUMN image VARCHAR(255) NULL AFTER value_ar',
        ];
    
        foreach ($columns as $column => $sql) {
            if (!Schema::hasColumn('product_option_values', $column)) {
                DB::statement($sql);
                $this->info("✅ Added {$column} to product_option_values table");
            } else {
                $this->warn("⚠️ {$column} already exists in product_option_values table");
            }
        }
    
        // جعل الحقول القديمة nullable
        $this->makeColumnsNullable('product_option_values', ['value']);
    
        $this->info('✅ Product option values table multilingual setup completed');
    }

    private function setupOrdersTable()
    {
        $this->info('📋 Setting up orders table...');

        if (!Schema::hasTable('orders')) {
            $this->error('Orders table does not exist!');
            return;
        }

        // في جدول الطلبات، قد نضيف حقول للغة إذا كانت هناك حقول مثل notes أو status
        // لكن معظم حقول الطلبات لا تحتاج multilingual (مثل total, status, etc.)
        
        // مثال: إذا كان هناك حقل notes نضيف له multilingual
        if (Schema::hasColumn('orders', 'notes')) {
            $columns = [
                'notes_en' => 'ALTER TABLE orders ADD COLUMN notes_en TEXT AFTER notes',
                'notes_ar' => 'ALTER TABLE orders ADD COLUMN notes_ar TEXT AFTER notes_en',
            ];

            foreach ($columns as $column => $sql) {
                if (!Schema::hasColumn('orders', $column)) {
                    DB::statement($sql);
                    $this->info("✅ Added {$column} to orders table");
                } else {
                    $this->warn("⚠️ {$column} already exists in orders table");
                }
            }

            // جعل الحقل القديم nullable
            $this->makeColumnsNullable('orders', ['notes']);
        }

        // إضافة أي حقول أخرى للطلبات إذا لزم الأمر
        
        $this->info('✅ Orders table setup completed');
    }

    private function setupOffersTable()
    {
        $this->info('🎟️ Setting up offers table...');

        if (!Schema::hasTable('offers')) {
            $this->error('Offers table does not exist!');
            return;
        }

        $columns = [
            'title_en' => 'ALTER TABLE offers ADD COLUMN title_en VARCHAR(255) AFTER title',
            'title_ar' => 'ALTER TABLE offers ADD COLUMN title_ar VARCHAR(255) AFTER title_en',
            'description_en' => 'ALTER TABLE offers ADD COLUMN description_en TEXT AFTER description',
            'description_ar' => 'ALTER TABLE offers ADD COLUMN description_ar TEXT AFTER description_en',
        ];

        foreach ($columns as $column => $sql) {
            if (!Schema::hasColumn('offers', $column)) {
                DB::statement($sql);
                $this->info("✅ Added {$column} to offers table");
            } else {
                $this->warn("⚠️ {$column} already exists in offers table");
            }
        }

        // جعل الحقول القديمة nullable
        $this->makeColumnsNullable('offers', ['title', 'description']);
        
        $this->info('✅ Offers table multilingual setup completed');
    }

    private function setupProductOptionsTable()
    {
        $this->info('⚙️ Setting up product_options table...');

        if (!Schema::hasTable('product_options')) {
            $this->warn('⚠️ product_options table does not exist, skipping...');
            return;
        }

        // إضافة الحقول الجديدة للاسم multilingual
        $columns = [
            'name_ar' => 'ALTER TABLE product_options ADD COLUMN name_ar VARCHAR(255) NULL AFTER name',
        ];

        foreach ($columns as $column => $sql) {
            if (!Schema::hasColumn('product_options', $column)) {
                DB::statement($sql);
                $this->info("✅ Added {$column} to product_options table");
            } else {
                $this->warn("⚠️ {$column} already exists in product_options table");
            }
        }

        // جعل الحقول القديمة nullable
        $this->makeColumnsNullable('product_options', ['name']);

        $this->info('✅ Product options table multilingual setup completed');
    }

    /**
     * دالة مساعدة لجعل الحقول nullable
     */
    private function makeColumnsNullable($table, $columns)
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                try {
                    // الحصول على نوع العمود لمعرفة إذا كان TEXT أو VARCHAR
                    $columnType = DB::selectOne("
                        SELECT DATA_TYPE 
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = '{$table}' 
                        AND COLUMN_NAME = '{$column}'
                    ");

                    if ($columnType) {
                        $type = $columnType->DATA_TYPE;
                        if ($type === 'text') {
                            DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} TEXT NULL");
                        } else {
                            DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} VARCHAR(255) NULL");
                        }
                        $this->info("✅ Made {$column} nullable in {$table} table");
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️ Could not make {$column} nullable: " . $e->getMessage());
                }
            }
        }
    }
}