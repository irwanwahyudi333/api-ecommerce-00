<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $col = DB::selectOne(
                "SELECT data_type FROM information_schema.columns WHERE table_name = 'order_product' AND column_name = 'order_quantity'"
            );

            if ($col && $col->data_type === 'character varying') {
                DB::statement('ALTER TABLE order_product ALTER COLUMN order_quantity TYPE integer USING (order_quantity::integer);');
            }
        } else {
            Schema::table('order_product', function (Blueprint $table) {
                $table->integer('order_quantity')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_product ALTER COLUMN order_quantity TYPE varchar(255) USING (order_quantity::varchar);');
        } else {
            Schema::table('order_product', function (Blueprint $table) {
                $table->string('order_quantity')->change();
            });
        }
    }
};
