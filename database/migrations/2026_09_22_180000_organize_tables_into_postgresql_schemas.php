<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Schema mapping for PostgreSQL.
     *
     * @var array<string, array<int, string>>
     */
    private array $schemaMapping = [
        'master' => [
            'users',
            'user_profiles',
            'user_sessions',
            'address',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
            'personal_access_tokens',
            'password_reset_tokens',
            'password_history',
            'products',
            'categories',
            'category_product',
            'attributes',
            'attribute_values',
            'attribute_product',
            'variation_options',
            'products_meta',
            'tags',
            'product_tag',
            'types',
            'manufacturers',
            'authors',
            'digital_files',
            'availabilities',
            'resources',
            'deposit_product',
            'feature_product',
            'person_product',
        ],
        'sales' => [
            'orders',
            'order_product',
            'order_wallet_points',
            'ordered_files',
            'download_tokens',
            'coupons',
            'flash_sales',
            'flash_sale_products',
            'flash_sale_requests',
            'flash_sale_requests_products',
            'wishlists',
            'reviews',
        ],
        'payment' => [
            'payment_gateways',
            'payment_methods',
            'payment_intents',
            'wallets',
            'balances',
            'refunds',
            'refund_policies',
            'refund_reasons',
            'currencies',
            'tax_classes',
        ],
        'vendor' => [
            'shops',
            'user_shop',
            'category_shop',
            'commissions',
            'withdraws',
            'ownership_transfers',
            'became_sellers',
        ],
        'shipping' => [
            'shipping_classes',
            'delivery_times',
            'logistics',
            'pickup_location_product',
            'dropoff_location_product',
        ],
        'communication' => [
            'conversations',
            'messages',
            'participants',
            'feedbacks',
            'questions',
            'abusive_reports',
            'faqs',
            'store_notices',
            'store_notice_shop',
            'store_notice_user',
            'store_notice_read',
            'notify_logs',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->schemaMapping as $schema => $tables) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\";");

            foreach ($tables as $table) {
                $existsInPublic = DB::selectOne(
                    "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?",
                    [$table]
                );

                if ($existsInPublic) {
                    DB::statement("ALTER TABLE public.\"{$table}\" SET SCHEMA \"{$schema}\";");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->schemaMapping as $schema => $tables) {
            foreach ($tables as $table) {
                $existsInSchema = DB::selectOne(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                    [$schema, $table]
                );

                if ($existsInSchema) {
                    DB::statement("ALTER TABLE \"{$schema}\".\"{$table}\" SET SCHEMA public;");
                }
            }

            DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" RESTRICT;");
        }
    }
};
