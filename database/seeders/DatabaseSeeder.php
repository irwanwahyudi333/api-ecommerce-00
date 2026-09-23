<?php

namespace Database\Seeders;

use App\Enums\ResourceType;
use App\Models\Address;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Author;
use App\Models\Availability;
use App\Models\Balance;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Commission;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\DeliveryTime;
use App\Models\DigitalFile;
use App\Models\DownloadToken;
use App\Models\Faqs;
use App\Models\Feedback;
use App\Models\FlashSale;
use App\Models\FlashSaleRequest;
use App\Models\Language;
use App\Models\Manufacturer;
use App\Models\Message;
use App\Models\NotifyLogs;
use App\Models\Order;
use App\Models\OrderedFile;
use App\Models\OrderWalletPoint;
use App\Models\OwnershipTransfer;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Provider;
use App\Models\Question;
use App\Models\Refund;
use App\Models\Resource;
use App\Models\Review;
use App\Models\Settings;
use App\Models\Shipping;
use App\Models\Shop;
use App\Models\StoreNotice;
use App\Models\Tag;
use App\Models\Tax;
use App\Models\TermsAndConditions;
use App\Models\Type;
use App\Models\User;
use App\Models\Variation;
use App\Models\Wallet;
use App\Models\Wishlist;
use App\Models\Withdraw;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Matikan foreign key checks sementara untuk truncate (opsional)
        Schema::disableForeignKeyConstraints();

        // ============================================================
        // 1. DATA DASAR (tanpa foreign key atau hanya self-reference)
        // ============================================================

        // Language (jika translation diaktifkan)
        if (env('TRANSLATION_ENABLED', false)) {
            Language::factory(3)->create();
        }

        // Settings (minimal 1 per language)
        Settings::factory()->create(['language' => 'en']);
        Settings::factory()->create(['language' => 'id']);

        // Tax classes
        Tax::factory(5)->create();
        Tax::factory()->global()->create();

        // Shipping classes
        Shipping::factory(3)->create();

        // Delivery times
        DeliveryTime::factory(4)->create();

        // Resources (dropoff, pickup, dll)
        Resource::factory(3)->create([
            'type' => ResourceType::DROPOFF_LOCATION,
        ]);

        Resource::factory(3)->create([
            'type' => ResourceType::PICKUP_LOCATION,
        ]);

        Resource::factory(2)->create([
            'type' => ResourceType::DEPOSIT,
        ]);

        Resource::factory(2)->create([
            'type' => ResourceType::PERSON,
        ]);

        Resource::factory(2)->create([
            'type' => ResourceType::FEATURES,
        ]);

        // Types (kategori produk utama)
        Type::factory(5)->create();

        // Categories (dengan hierarki)
        Category::factory(10)->create();
        Category::factory(5)->withParent()->create();
        Category::factory(3)->withParent(Category::inRandomOrder()->first())->create();

        // Tags
        Tag::factory(15)->create();

        // ============================================================
        // 2. USER & SHOP
        // ============================================================

        // Admin user (untuk keperluan akses)
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        // Jika menggunakan spatie/permission, assign role
        // $admin->assignRole('super_admin');

        // User biasa dengan profil
        $users = User::factory(2)
            ->active()
            ->has(Profile::factory(), 'profile')
            ->create();

        // Shop untuk beberapa user (owner)
        $shops = Shop::factory(1)->create([
            'owner_id' => $users->random()->id,
        ]);

        // Staff/user ke shop (many-to-many)
        foreach ($shops as $shop) {
            $randomUsers = $users->random(rand(1, min(2, $users->count())));
            foreach ($randomUsers as $user) {
                DB::table('user_shop')->insert([
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);
            }
            // Balance untuk setiap shop
            Balance::factory()->create(['shop_id' => $shop->id]);
        }

        // ============================================================
        // 3. PRODUK DAN RELASINYA
        // ============================================================

        Author::factory(10)->create();
        Manufacturer::factory(8)->create();

        // Atribut dan nilai
        $attributes = Attribute::factory(1)->create();
        AttributeValue::factory(3)->create([
            'attribute_id' => $attributes->random()->id,
        ]);

        $products = collect();
        foreach ($shops as $shop) {
            $productsForShop = Product::factory(2)
                ->for($shop)
                ->withAuthor(Author::inRandomOrder()->first())
                ->withManufacturer(Manufacturer::inRandomOrder()->first())
                ->create();

            foreach ($productsForShop as $product) {
                // Attach categories
                $categories = Category::inRandomOrder()->limit(rand(1, 3))->get();
                $product->categories()->attach($categories);

                // Attach tags
                $tags = Tag::inRandomOrder()->limit(rand(2, 5))->get();
                $product->tags()->attach($tags);

                // Attach attribute values
                $attrValues = AttributeValue::inRandomOrder()->limit(rand(1, 4))->get();
                $product->variations()->attach($attrValues);

                // Digital file jika digital
                if ($product->is_digital) {
                    DigitalFile::factory()->forProduct($product->id)->create();
                }

                // Variation options jika variable
                if ($product->product_type == 'variable') {
                    Variation::factory(rand(2, 5))->create(['product_id' => $product->id]);
                }

                // Availabilities jika rental
                if ($product->product_type == 'rental') {
                    Availability::factory(3)->forProduct($product)->create();
                }

                // Hubungan dengan resources
                $dropoff = Resource::where('type', ResourceType::DROPOFF_LOCATION)->inRandomOrder()->first();

                if ($dropoff) {
                    DB::table('dropoff_location_product')->insert([
                        'resource_id' => $dropoff->id,
                        'product_id' => $product->id,
                    ]);
                }

                $pickup = Resource::where('type', ResourceType::PICKUP_LOCATION)->inRandomOrder()->first();

                if ($pickup) {
                    DB::table('pickup_location_product')->insert([
                        'resource_id' => $pickup->id,
                        'product_id' => $product->id,
                    ]);
                }
            }
            $products = $products->merge($productsForShop);
        }

        // ============================================================
        // 4. ORDER & PEMBAYARAN
        // ============================================================

        $orders = collect();
        foreach ($users as $user) {
            $orderCount = 1;
            for ($i = 0; $i < $orderCount; $i++) {
                $shop = $shops->random();
                $order = Order::factory()->create([
                    'customer_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);

                $shopProducts = $products->where('shop_id', $shop->id)->random(min(3, $products->count()));
                $total = 0;
                foreach ($shopProducts as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = $product->sale_price ?? $product->price;
                    $subtotal = $quantity * $unitPrice;
                    $order->products()->attach($product->id, [
                        'order_quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);
                    $total += $subtotal;
                }
                $order->update([
                    'amount' => $total,
                    'total' => $total,
                    'paid_total' => $total,
                ]);

                PaymentIntent::factory()->for($order)->create();

                if ($order->order_status == 'completed' && rand(0, 2) == 1) {
                    Refund::factory()->for($order)->create();
                }

                $orders->push($order);
            }
        }

        // ============================================================
        // 5. ORDER WALLET POINTS (menggunakan factory yang sudah diperbarui)
        // ============================================================
        foreach ($orders->random(min(10, $orders->count())) as $order) {
            OrderWalletPoint::factory()
                ->forOrder($order)
                ->amount(rand(10, 100))
                ->create();
        }

        // ============================================================
        // 6. KOMUNIKASI & NOTIFIKASI
        // ============================================================
        foreach ($shops->random(min(1, $shops->count())) as $shop) {
            $conversation = Conversation::factory()
                ->for($shop)
                ->for($users->random(), 'user')
                ->create();

            Message::factory(rand(3, 10))
                ->for($conversation)
                ->for($users->random(), 'user')
                ->create();
        }

        NotifyLogs::factory(30)->create();

        // ============================================================
        // 7. REVIEW & FEEDBACK
        // ============================================================

        foreach ($products->random(min(1, $products->count())) as $product) {
            $order = $orders->where('shop_id', $product->shop_id)->first();
            if ($order) {
                $review = Review::factory()
                    ->for($order)
                    ->for($order->customer, 'user')
                    ->for($product->shop)
                    ->for($product)
                    ->create(['rating' => rand(3, 5)]);

                Feedback::factory()->for($review, 'model')->create();
            }
        }

        foreach ($products->random(min(1, $products->count())) as $product) {
            Question::factory()
                ->for($product)
                ->for($product->shop)
                ->for($users->random(), 'user')
                ->create();
        }

        foreach ($users as $user) {
            $randomProducts = $products->random(rand(1, min(1, $products->count())));
            foreach ($randomProducts as $product) {
                Wishlist::factory()
                    ->for($user)
                    ->for($product)
                    ->create();
            }
        }

        // ============================================================
        // 8. FLASH SALE & COUPON
        // ============================================================

        $flashSale = FlashSale::factory()->active()->create();
        $flashSaleProducts = $products->random(min(1, $products->count()));
        foreach ($flashSaleProducts as $product) {
            DB::table('flash_sale_products')->insert([
                'flash_sale_id' => $flashSale->id,
                'product_id' => $product->id,
            ]);
        }

        $flashSaleRequest = FlashSaleRequest::factory()
            ->for($flashSale)
            ->create();
        foreach ($products->random(min(1, $products->count())) as $product) {
            DB::table('flash_sale_requests_products')->insert([
                'flash_sale_requests_id' => $flashSaleRequest->id,
                'product_id' => $product->id,
            ]);
        }

        Coupon::factory(10)->create(['shop_id' => null]);
        foreach ($shops as $shop) {
            Coupon::factory(3)->for($shop, 'shop')->create();
        }

        // ============================================================
        // 9. STORE NOTICES
        // ============================================================

        $storeNotices = StoreNotice::factory(1)->create();
        foreach ($storeNotices as $notice) {
            $notice->users()->attach($users->random(min(1, $users->count())));
            $notice->shops()->attach($shops->random(min(1, $shops->count())));
            foreach ($users->random(min(1, $users->count())) as $user) {
                DB::table('store_notice_read')->insert([
                    'store_notice_id' => $notice->id,
                    'user_id' => $user->id,
                    'is_read' => true,
                ]);
            }
        }

        // ============================================================
        // 10. WITHDRAW & OWNERSHIP TRANSFER
        // ============================================================

        foreach ($shops as $shop) {
            Withdraw::factory(rand(0, 1))->for($shop)->create();
        }

        foreach ($shops->random(min(1, $shops->count())) as $shop) {
            OwnershipTransfer::factory()
                ->for($shop)
                ->create([
                    'from' => $shop->owner_id,
                    'to' => $users->whereNotIn('id', $shop->owner_id)->random()->id,
                ]);
        }

        // ============================================================
        // 11. FAQ, TERMS & CONDITIONS, BANNERS
        // ============================================================

        Faqs::factory(1)->create();
        foreach ($shops->random(min(1, $shops->count())) as $shop) {
            Faqs::factory(1)->for($shop, 'shop')->create();
        }

        TermsAndConditions::factory(1)->create();
        foreach ($shops->random(min(1, $shops->count())) as $shop) {
            TermsAndConditions::factory(1)->for($shop, 'shop')->create();
        }

        foreach (Type::all() as $type) {
            Banner::factory(1)->for($type)->create();
        }

        // ============================================================
        // 12. COMMISSION & ADDRESS
        // ============================================================

        Commission::factory(1)->create();

        foreach ($users as $user) {
            Address::factory(rand(1, 1))->for($user, 'customer')->create();
        }

        // ============================================================
        // 13. PROVIDER, PAYMENT GATEWAY & METHOD
        // ============================================================

        Provider::factory(1)->create();

        foreach ($users->random(min(1, $users->count())) as $user) {
            $gateway = PaymentGateway::factory()->for($user)->create();
            PaymentMethod::factory(rand(1, 1))->for($gateway)->create();
        }

        // ============================================================
        // 14. WALLET
        // ============================================================

        foreach ($users as $user) {
            Wallet::factory()->for($user, 'customer')->create();
        }

        // ============================================================
        // 15. DIGITAL FILES & ORDERED FILES
        // ============================================================

        $digitalProducts = Product::where('is_digital', true)->get();
        foreach ($digitalProducts as $product) {
            $digitalFile = DigitalFile::where('fileable_type', 'App\Models\Product')
                ->where('fileable_id', $product->id)
                ->first();
            if ($digitalFile) {
                $ordersWithProduct = Order::whereHas('products', fn ($q) => $q->where('product_id', $product->id))->get();
                foreach ($ordersWithProduct as $order) {
                    OrderedFile::factory()->create([
                        'digital_file_id' => $digitalFile->id,
                        'tracking_number' => $order->tracking_number,
                        'customer_id' => $order->customer_id,
                    ]);
                }
            }
        }

        DownloadToken::factory(2)->create();

        // Aktifkan kembali foreign key checks
        Schema::enableForeignKeyConstraints();

        $this->call(CurrencySeeder::class);

        $this->command->info('Database seeding completed successfully!');
    }
}
