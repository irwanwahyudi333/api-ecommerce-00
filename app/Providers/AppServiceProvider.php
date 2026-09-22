<?php

namespace App\Providers;

use App\Models\AbusiveReport;
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Author;
use App\Models\BecameSeller;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\DeliveryTime;
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
use App\Models\OwnershipTransfer;
use App\Models\Product;
use App\Models\Question;
use App\Models\RefundReason;
use App\Models\Resource;
use App\Models\Settings;
use App\Models\Shipping;
use App\Models\Shop;
use App\Models\Tax;
use App\Models\TermsAndConditions;
use App\Models\Type;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Withdraw;
use App\Modules\AbusiveReport\Policies\AbusiveReportPolicy;
use App\Modules\Address\Policies\AddressPolicy;
use App\Modules\Address\Services\AddressFormatterService;
use App\Modules\Attachment\Policies\AttachmentPolicy;
use App\Modules\Attribute\Policies\AttributePolicy;
use App\Modules\AttributeValue\Policies\AttributeValuePolicy;
use App\Modules\Author\Policies\AuthorPolicy;
use App\Modules\BecameSeller\Policies\BecameSellerPolicy;
use App\Modules\Category\Policies\CategoryPolicy;
use App\Modules\Conversation\Policies\ConversationPolicy;
use App\Modules\Coupon\Policies\CouponPolicy;
use App\Modules\DeliveryTime\Policies\DeliveryTimePolicy;
use App\Modules\Download\Policies\DownloadPolicy;
use App\Modules\Faqs\Policies\FaqsPolicy;
use App\Modules\Feedback\Policies\FeedbackPolicy;
use App\Modules\FlashSale\Policies\FlashSalePolicy;
use App\Modules\FlashSaleRequest\Policies\FlashSaleRequestPolicy;
use App\Modules\Language\Policies\LanguagePolicy;
use App\Modules\Manufacturer\Policies\ManufacturerPolicy;
use App\Modules\Message\Policies\MessagePolicy;
use App\Modules\NotifyLogs\Policies\NotifyLogsPolicy;
use App\Modules\Order\Policies\OrderPolicy;
use App\Modules\Otp\Services\OtpService;
use App\Modules\OwnershipTransfer\Policies\OwnershipTransferPolicy;
use App\Modules\Product\Policies\ProductPolicy;
use App\Modules\Question\Policies\QuestionPolicy;
use App\Modules\RefundReason\Policies\RefundReasonPolicy;
use App\Modules\Resource\Policies\ResourcePolicy;
use App\Modules\Settings\Policies\SettingsPolicy;
use App\Modules\Shipping\Policies\ShippingPolicy;
use App\Modules\Shop\Policies\ShopPolicy;
use App\Modules\Tax\Policies\TaxPolicy;
use App\Modules\Terms\Policies\TermsPolicy;
use App\Modules\Type\Policies\TypePolicy;
use App\Modules\User\Policies\UserPolicy;
use App\Modules\Wishlist\Policies\WishlistPolicy;
use App\Modules\Withdraw\Policies\WithdrawPolicy;
use App\Services\CurrencyFormatterService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService;
        });

        $this->app->bind(AddressFormatterService::class, function ($app) {
            return new AddressFormatterService;
        });
        $this->app->bind(CurrencyFormatterService::class, function ($app) {
            return new CurrencyFormatterService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip().'|'.$request->input('email')
            );
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->ip().'|'.$request->input('phone_number')
            );
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->ip().'|'.$request->input('email')
            );
        });

        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(AbusiveReport::class, AbusiveReportPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(Attribute::class, AttributePolicy::class);
        Gate::policy(AttributeValue::class, AttributeValuePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(BecameSeller::class, BecameSellerPolicy::class);
        Gate::policy(Author::class, AuthorPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(OrderedFile::class, DownloadPolicy::class);
        Gate::policy(Faqs::class, FaqsPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(FlashSale::class, FlashSalePolicy::class);
        Gate::policy(FlashSaleRequest::class, FlashSaleRequestPolicy::class);
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(Manufacturer::class, ManufacturerPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(NotifyLogs::class, NotifyLogsPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(RefundReason::class, RefundReasonPolicy::class);
        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(Shipping::class, ShippingPolicy::class);
        Gate::policy(Tax::class, TaxPolicy::class);
        Gate::policy(Type::class, TypePolicy::class);
        Gate::policy(Wishlist::class, WishlistPolicy::class);
        Gate::policy(DeliveryTime::class, DeliveryTimePolicy::class);
        Gate::policy(OwnershipTransfer::class, OwnershipTransferPolicy::class);
        Gate::policy(Settings::class, SettingsPolicy::class);
        Gate::policy(Shop::class, ShopPolicy::class);
        Gate::policy(TermsAndConditions::class, TermsPolicy::class);
        Gate::policy(Type::class, TypePolicy::class);
        Gate::policy(Withdraw::class, WithdrawPolicy::class);
    }
}
