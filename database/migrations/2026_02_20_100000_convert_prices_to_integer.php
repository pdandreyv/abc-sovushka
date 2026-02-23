<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Перевести все поля с ценами/суммами в integer (рубли без копеек).
     * Существующие значения округляются вверх (CEIL).
     */
    public function up(): void
    {
        $tables = [
            'subscription_orders' => [
                'sum_subscription' => 'nullable',
                'sum_without_discount' => 'nullable',
                'sum_next_pay' => 'nullable',
            ],
            'subscription_tariffs' => [
                'price' => 'not_null',
            ],
            'promotions' => [
                'special_price' => 'not_null',
            ],
            'subscription_payments_logs' => [
                'amount' => 'not_null',
            ],
        ];

        foreach ($tables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $nullable) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $expr = $nullable === 'nullable'
                    ? "CEIL(COALESCE(`{$column}`, 0))"
                    : "CEIL(IFNULL(`{$column}`, 0))";
                DB::statement("UPDATE `{$table}` SET `{$column}` = {$expr}");

                $nullAttr = $nullable === 'nullable' ? ' NULL' : ' NOT NULL';
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT UNSIGNED{$nullAttr}");
            }
        }
    }

    /**
     * Вернуть типы decimal(10,2).
     */
    public function down(): void
    {
        $definitions = [
            'subscription_orders' => [
                ['sum_subscription', false],
                ['sum_without_discount', false],
                ['sum_next_pay', true],
            ],
            'subscription_tariffs' => [
                ['price', false],
            ],
            'promotions' => [
                ['special_price', false],
            ],
            'subscription_payments_logs' => [
                ['amount', false],
            ],
        ];

        foreach ($definitions as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as [$column, $nullable]) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $nullAttr = $nullable ? ' NULL' : ' NOT NULL';
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` DECIMAL(10,2){$nullAttr}");
            }
        }
    }
};
