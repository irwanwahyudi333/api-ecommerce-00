<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan FULLTEXT index jika driver database adalah MySQL, MariaDB, atau PostgreSQL
        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->fullText('tracking_number');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->fullText(['name', 'email']);
            });
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropFullText('tracking_number');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropFullText(['name', 'email']);
            });
        }
    }
};
