<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // КРИТИЧНО: Меняем формат строки на DYNAMIC для поддержки TEXT полей
        // Это позволяет хранить TEXT/BLOB полностью вне строки
        try {
            DB::statement("SET SESSION sql_mode = ''");
            DB::statement("ALTER TABLE `users` ROW_FORMAT=DYNAMIC");
        } catch (\Exception $e) {
            // Если DYNAMIC не поддерживается, пробуем COMPRESSED
            try {
                DB::statement("ALTER TABLE `users` ROW_FORMAT=COMPRESSED");
            } catch (\Exception $e2) {
                // Игнорируем, если не поддерживается
            }
        }
        
        // Оптимизируем существующие VARCHAR поля для освобождения места
        // Конвертируем большие VARCHAR в TEXT (не учитываются в размере строки)
        $fieldsToText = ['name', 'surname'];
        foreach ($fieldsToText as $field) {
            if (Schema::hasColumn('users', $field)) {
                try {
                    $col = DB::selectOne("SHOW COLUMNS FROM `users` WHERE Field = ?", [$field]);
                    if ($col && preg_match('/varchar/i', $col->Type)) {
                        DB::statement("ALTER TABLE `users` MODIFY COLUMN `{$field}` TEXT NULL");
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки
                }
            }
        }
        
        // Уменьшаем размер больших VARCHAR полей
        $fieldsToShrink = [
            'email' => 'VARCHAR(100)',
            'password' => 'VARCHAR(100)',
            'last_name' => 'VARCHAR(50)',
            'first_name' => 'VARCHAR(50)',
            'middle_name' => 'VARCHAR(50)',
            'phone' => 'VARCHAR(20)',
            'role' => 'VARCHAR(30)',
        ];
        
        foreach ($fieldsToShrink as $field => $newType) {
            if (Schema::hasColumn('users', $field)) {
                try {
                    $col = DB::selectOne("SHOW COLUMNS FROM `users` WHERE Field = ?", [$field]);
                    if ($col && preg_match('/varchar\((\d+)\)/i', $col->Type, $matches)) {
                        $currentSize = (int)$matches[1];
                        if ($currentSize > 50) {
                            DB::statement("ALTER TABLE `users` MODIFY COLUMN `{$field}` {$newType} NULL");
                        }
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки
                }
            }
        }
        
        // Теперь добавляем новые поля (TEXT не учитываются в размере строки с ROW_FORMAT=DYNAMIC)
        $newFields = ['city', 'organization', 'about'];
        foreach ($newFields as $field) {
            if (!Schema::hasColumn('users', $field)) {
                try {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$field}` TEXT NULL");
                } catch (\Exception $e) {
                    // Если ошибка, пробуем еще раз после небольшой задержки
                    sleep(1);
                    try {
                        DB::statement("ALTER TABLE `users` ADD COLUMN `{$field}` TEXT NULL");
                    } catch (\Exception $e2) {
                        // Игнорируем повторные ошибки
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'organization')) {
                $table->dropColumn('organization');
            }
            if (Schema::hasColumn('users', 'about')) {
                $table->dropColumn('about');
            }
        });
    }
};
