<?php

use App\Enums\Permission;
use App\Modules\AbusiveReport\Http\Controllers\AbusiveReportController;
use App\Modules\Address\Http\Controllers\AddressController;
use App\Modules\Analytics\Http\Controllers\AnalyticsController;
use App\Modules\Attachment\Http\Controllers\AttachmentController;
use App\Modules\Attribute\Http\Controllers\AttributeController;
use App\Modules\AttributeValue\Http\Controllers\AttributeValueController;
use App\Modules\Author\Http\Controllers\AuthorController;
use App\Modules\BecameSeller\Http\Controllers\BecameSellerController;
use App\Modules\Category\Http\Controllers\CategoryController;
use App\Modules\Checkout\Http\Controllers\CheckoutController;
use App\Modules\Conversation\Http\Controllers\ConversationController;
use App\Modules\Coupon\Http\Controllers\CouponController;
use App\Modules\DeliveryTime\Http\Controllers\DeliveryTimeController;
use App\Modules\Download\Http\Controllers\DownloadController;
use App\Modules\Faqs\Http\Controllers\FaqsController;
use App\Modules\Feedback\Http\Controllers\FeedbackController;
use App\Modules\FlashSale\Http\Controllers\FlashSaleController;
use App\Modules\FlashSaleRequest\Http\Controllers\FlashSaleRequestController;
use App\Modules\Language\Http\Controllers\LanguageController;
use App\Modules\Manufacturer\Http\Controllers\ManufacturerController;
use App\Modules\Message\Http\Controllers\MessageController;
use App\Modules\NotifyLogs\Http\Controllers\NotifyLogsController;
use App\Modules\Order\Http\Controllers\OrderQueryController;
use App\Modules\Order\Http\Controllers\OrderTransactionController;
use App\Modules\OwnershipTransfer\Http\Controllers\OwnershipTransferController;
use App\Modules\PaymentIntent\Http\Controllers\PaymentIntentController;
use App\Modules\PaymentMethod\Http\Controllers\PaymentMethodController;
use App\Modules\Product\Http\Controllers\ProductController;
use App\Modules\Product\Http\Controllers\ProductCrudController;
use App\Modules\Product\Http\Controllers\ProductInventoryController;
use App\Modules\Product\Http\Controllers\ProductMetricController;
use App\Modules\Product\Http\Controllers\ProductQueryController;
use App\Modules\Product\Http\Controllers\ProductRentalController;
use App\Modules\Question\Http\Controllers\QuestionController;
use App\Modules\Refund\Http\Controllers\RefundController;
use App\Modules\Refund\Http\Controllers\RefundPolicyController;
use App\Modules\RefundReason\Http\Controllers\RefundReasonController;
use App\Modules\Resource\Http\Controllers\ResourceController;
use App\Modules\Review\Http\Controllers\ReviewController;
use App\Modules\Settings\Http\Controllers\SettingsController;
use App\Modules\Shipping\Http\Controllers\ShippingController;
use App\Modules\Shop\Http\Controllers\ShopController;
use App\Modules\Shop\Http\Controllers\ShopQueryController;
use App\Modules\StoreNotice\Http\Controllers\StoreNoticeController;
use App\Modules\Tag\Http\Controllers\TagController;
use App\Modules\Tax\Http\Controllers\TaxController;
use App\Modules\Terms\Http\Controllers\TermsAndConditionsController;
use App\Modules\Type\Http\Controllers\TypeController;
use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\ProfileController;
use App\Modules\User\Http\Controllers\UserManagementController;
use App\Modules\User\Http\Controllers\UserSecurityController;
use App\Modules\Webhook\Http\Controllers\WebhookController;
use App\Modules\Wishlist\Http\Controllers\WishlistController;
use App\Modules\Withdraw\Http\Controllers\WithdrawController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by(
        $request->ip().'|'.$request->input('provider')
    );
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// ========================
// PUBLIC ROUTES (no auth)
// ========================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/social-login', [AuthController::class, 'socialLogin']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'emailVerify'])->middleware('signed')->name('verification.verify');
Route::middleware('auth:sanctum')->post('/email/verification-notification', [AuthController::class, 'emailVerifyNotification']);
Route::middleware('auth:sanctum')->get('/email/verified', [AuthController::class, 'getEmailVerify']);

// Product public queries
Route::get('/popular-products', [ProductMetricController::class, 'popular']);
Route::get('/best-selling-products', [ProductMetricController::class, 'bestSelling']);
Route::get('/check-availability', [ProductRentalController::class, 'checkAvailability'])->name('products.rental.checkAvailability');
Route::get('/products/calculate-rental-price', [ProductRentalController::class, 'calculateRentalPrice']);
Route::get('/products/search', [ProductQueryController::class, 'search']);
Route::apiResource('/products', ProductQueryController::class)->only(['index', 'show']);

// Authors
Route::get('/top-authors', [AuthorController::class, 'topAuthor']);
Route::apiResource('/authors', AuthorController::class)->only(['index', 'show']);

// Manufacturers
Route::get('/top-manufacturers', [ManufacturerController::class, 'topManufacturer']);
Route::apiResource('/manufacturers', ManufacturerController::class)->only(['index', 'show']);

// Types
Route::apiResource('/types', TypeController::class)->only(['index', 'show']);

// Attachments (public read)
Route::apiResource('/attachments', AttachmentController::class)->only(['index', 'show']);

// Categories
Route::apiResource('/categories', CategoryController::class)->only(['index', 'show']);
Route::get('/featured-categories', [CategoryController::class, 'fetchFeaturedCategories']);

// Delivery times
Route::apiResource('/delivery-times', DeliveryTimeController::class)->only(['index', 'show']);

// Languages
Route::apiResource('/languages', LanguageController::class)->only(['index', 'show']);

// Tags (public read)
Route::apiResource('/tags', TagController::class)->only(['index', 'show']);

// Refund reasons
Route::apiResource('/refund-reasons', RefundReasonController::class)->only(['index', 'show']);

// Resources
Route::apiResource('/resources', ResourceController::class)->only(['index', 'show']);

// Coupon verify (public)
Route::post('/coupons/verify', [CouponController::class, 'verify']);

Route::group(['middleware' => ['auth:sanctum', 'email.verified', 'permission:'.Permission::CUSTOMER->value]], function () {
    // Coupon (index & show) - hanya untuk customer yang login
    Route::apiResource('/coupons', CouponController::class)->only(['index', 'show']);
});

// Attributes (public read)
Route::apiResource('/attributes', AttributeController::class)->only(['index', 'show']);
Route::post('/import-attributes', [AttributeController::class, 'importAttributes']); // public? consider auth later
Route::get('/export-attributes/{shop_id}', [AttributeController::class, 'exportAttributes']);

// Shops (public listing & detail)
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{shop:slug}', [ShopController::class, 'show']);
Route::get('/near-by-shop', [ShopController::class, 'nearByShop']);
Route::get('/shops/search', [ShopQueryController::class, 'search']);

// Settings (public read)
Route::apiResource('/settings', SettingsController::class)->only(['index']);

// Reviews (public read)
Route::apiResource('/reviews', ReviewController::class)->only(['index', 'show']);

// Questions (public read)
Route::apiResource('/questions', QuestionController::class)->only(['index', 'show']);

// Feedbacks (public read)
Route::apiResource('/feedbacks', FeedbackController::class)->only(['index', 'show']);

// Checkout verify (public)
Route::post('/orders/checkout/verify', [CheckoutController::class, 'verify']);

// OrderController (public)
Route::get('/orders/track/{identifier}', [OrderQueryController::class, 'show'])->name('orders.track');

// Payment intent
Route::get('/payment-intent', [PaymentIntentController::class, 'getPaymentIntent']);

// FAQs
Route::apiResource('/faqs', FaqsController::class)->only(['index', 'show']);

// Terms & conditions
Route::apiResource('/terms-and-conditions', TermsAndConditionsController::class)->only(['index', 'show']);

// Flash sales
Route::apiResource('/flash-sale', FlashSaleController::class)->only(['index', 'show']);

// Refund policies
Route::resource('/refund-policies', RefundPolicyController::class)->only(['index', 'show']);

// Store notices (public index)
Route::get('/store-notices', [StoreNoticeController::class, 'index'])->name('store-notices.index');

// Download token
Route::get('/download_url/token/{token}', [DownloadController::class, 'downloadFile'])->name('download_url.token');

// Became seller
Route::apiResource('/became-seller', BecameSellerController::class);

// ========================
// CUSTOMER (auth:sanctum, email.verified, permission:CUSTOMER)
// ========================
Route::group(['middleware' => ['auth:sanctum', 'email.verified', 'permission:'.Permission::CUSTOMER->value]], function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [ProfileController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'updateProfile']);
    Route::post('/me/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/me/avatar', [ProfileController::class, 'deleteAvatar']);

    // User management routes
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [ProfileController::class, 'updateUser']);
    Route::delete('/user', [ProfileController::class, 'deleteUser']);

    // Security
    Route::post('/me/change-password', [UserSecurityController::class, 'changePassword']);
    Route::post('/me/logout-all', [UserSecurityController::class, 'logoutFromAllDevices']);
    Route::get('/me/sessions', [UserSecurityController::class, 'viewActiveSessions']);
    Route::delete('/me/sessions/{sessionId}', [UserSecurityController::class, 'revokeSession']);

    // Coupon (index & show) - hanya untuk customer yang login
    Route::apiResource('/coupons', CouponController::class)->only(['index', 'show']);

    // Orders
    Route::get('/my-orders', [OrderQueryController::class, 'myOrders']);
    Route::post('/orders', [OrderTransactionController::class, 'store']);
    Route::get('/orders/{identifier}', [OrderQueryController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/cancel', [OrderTransactionController::class, 'cancel']);

    // Reviews (create, update)
    Route::apiResource('/reviews', ReviewController::class)->only(['store', 'update']);

    // Questions
    Route::apiResource('/questions', QuestionController::class)->only(['store']);
    Route::get('/my-questions', [QuestionController::class, 'myQuestions']);

    // Feedbacks
    Route::apiResource('/feedbacks', FeedbackController::class)->only(['store']);

    // Abusive reports
    Route::post('/abusive_reports', [AbusiveReportController::class, 'store']);
    Route::get('/my-reports', [AbusiveReportController::class, 'myReports']);

    // Conversations & Messages
    Route::apiResource('/conversations', ConversationController::class)->only(['index', 'store']);
    Route::get('/conversations/{conversation_id}', [ConversationController::class, 'show']);
    Route::get('/messages/conversations/{conversation_id}', [MessageController::class, 'index']);
    Route::post('/messages/conversations/{conversation_id}', [MessageController::class, 'store']);

    // Wishlists
    Route::post('/wishlists/toggle', [WishlistController::class, 'toggle']);
    Route::apiResource('/wishlists', WishlistController::class)->only(['index', 'store', 'destroy']);
    Route::get('/wishlists/in_wishlist/{product_id}', [WishlistController::class, 'in_wishlist']);
    Route::get('/my-wishlists', [ProductQueryController::class, 'myWishlists']);

    // Attachments (store, update, delete)
    Route::apiResource('/attachments', AttachmentController::class)->only(['store', 'update', 'destroy']);

    // Addresses
    Route::apiResource('addresses', AddressController::class)->middleware('throttle:60,1')->except(['show', 'index']);

    // Refunds
    Route::apiResource('/refunds', RefundController::class)->only(['index', 'store', 'show']);

    // Downloads
    Route::get('/downloads', [DownloadController::class, 'fetchDownloadableFiles']);
    Route::post('/downloads/digital_file', [DownloadController::class, 'generateDownloadableUrl']);

    // Shop follow
    Route::get('/followed-shops', [ShopController::class, 'userFollowedShops']);
    Route::get('/follow-shop', [ShopController::class, 'userFollowedShop']);
    Route::post('/follow-shop', [ShopController::class, 'handleFollowShop']);
    Route::get('/followed-shops-popular-products', [ProductQueryController::class, 'followedShopsPopularProducts']);

    // Payment methods
    Route::apiResource('/payment-methods', PaymentMethodController::class);
    Route::get('/payment-methods/gateways', [PaymentMethodController::class, 'gateways']);
    Route::post('/payment-methods/save', [PaymentMethodController::class, 'savePaymentMethod']);
    Route::post('/payment-methods/setup-intent', [PaymentMethodController::class, 'saveCardIntent']);
    Route::post('/payment-methods/set-default', [PaymentMethodController::class, 'setDefaultCard']);

    // Notify logs
    Route::apiResource('/notify-logs', NotifyLogsController::class)->except(['destroy']);
    Route::post('/notify-log-seen', [NotifyLogsController::class, 'readNotifyLogs']);
    Route::post('/notify-log-read-all', [NotifyLogsController::class, 'readAllNotifyLogs']);

    // Webhooks
    Route::apiResource('/webhooks', WebhookController::class);
});

// ========================
// STAFF & STORE OWNER (permission:STAFF|STORE_OWNER)
// ========================
Route::group(['middleware' => ['permission:'.Permission::STAFF->value.'|'.Permission::STORE_OWNER->value, 'auth:sanctum', 'email.verified']], function () {
    // Product routes
    Route::apiResource('/products', ProductCrudController::class)->only(['store', 'update', 'destroy']);
    Route::patch('/products/{id}/stock', [ProductCrudController::class, 'updateStock'])->name('products.update-stock');
    Route::patch('/products/{id}/status', [ProductCrudController::class, 'changeStatus'])->name('products.change-status');
    Route::get('/draft-products', [ProductController::class, 'draftedProducts'])->name('products.draft');
    Route::get('/products-stock', [ProductController::class, 'productStock'])->name('products.stock');
    Route::get('/products-by-flash-sale', [FlashSaleController::class, 'getProductsByFlashSale'])->name('products.by-flash-sale');

    // ResourceController
    Route::apiResource('/resources', ResourceController::class)->only(['store']);

    // Attributes
    Route::apiResource('/attributes', AttributeController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('/attribute-values', AttributeValueController::class)->only(['store', 'update', 'destroy']);

    // Order management
    Route::get('/orders', [OrderQueryController::class, 'index']);
    Route::patch('/orders/{id}/status', [OrderTransactionController::class, 'updateStatus']);
    Route::patch('/orders/{id}/payment-status', [OrderTransactionController::class, 'updatePaymentStatus']);
    Route::get('/shops/{shopId}/orders', [OrderQueryController::class, 'showByShop']);
    Route::get('/orders/stats', [OrderQueryController::class, 'stats']);

    // Question update
    Route::apiResource('/questions', QuestionController::class)->only(['update']);

    // Authors & Manufacturers (store)
    Route::apiResource('/authors', AuthorController::class)->only(['store']);
    Route::apiResource('/manufacturers', ManufacturerController::class)->only(['store']);

    // Store notices (full)
    Route::get('/store-notices/getStoreNoticeType', [StoreNoticeController::class, 'getStoreNoticeType']);
    Route::get('/store-notices/getUsersToNotify', [StoreNoticeController::class, 'getUsersToNotify']);
    Route::post('/store-notices/read/', [StoreNoticeController::class, 'readNotice']);
    Route::post('/store-notices/read-all', [StoreNoticeController::class, 'readAllNotice']);
    Route::apiResource('/store-notices', StoreNoticeController::class)->only(['show', 'store', 'update', 'destroy']);

    // FAQs (store, update, delete)
    Route::apiResource('/faqs', FaqsController::class)->only(['store', 'update', 'destroy']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'analytics']);
    Route::get('/low-stock-products', [AnalyticsController::class, 'lowStockProducts']);
    Route::get('/category-wise-product', [AnalyticsController::class, 'categoryWiseProduct']);
    Route::get('/category-wise-product-sale', [AnalyticsController::class, 'categoryWiseProductSale']);
    Route::get('/top-rate-product', [AnalyticsController::class, 'topRatedProducts']);

    // Coupon update (staff/store owner)
    Route::apiResource('/coupons', CouponController::class)->only(['update']);

    // Inventory management
    Route::get('/products/low-stock', [ProductInventoryController::class, 'lowStock']);
    Route::get('/products/{product}/inventory', [ProductInventoryController::class, 'show']);
    Route::put('/products/{product}/inventory', [ProductInventoryController::class, 'update']);
});

// ========================
// STORE OWNER ONLY (permission:STORE_OWNER)
// ========================
Route::group(['middleware' => ['auth:sanctum', 'email.verified', 'permission:'.Permission::STORE_OWNER->value]], function () {
    // Shop management
    Route::post('/shops', [ShopController::class, 'store']);
    Route::put('/shops/{shop}', [ShopController::class, 'update']);
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy']);
    Route::get('/my-shops', [ShopController::class, 'myShops']);
    Route::post('/transfer-shop-ownership', [ShopController::class, 'transferShopOwnership']);
    Route::post('/shops/{shop}/staffs', [ShopController::class, 'addStaff']);
    Route::delete('/staffs/{staff}', [ShopController::class, 'deleteStaff']);
    Route::post('/shops/{shop}/maintenance', [ShopController::class, 'shopMaintenanceEvent']);

    // Withdraws
    Route::apiResource('/withdraws', WithdrawController::class)->only(['store', 'index', 'show']);

    // Flash sales (store, update, delete)
    Route::apiResource('/flash-sale', FlashSaleController::class)->only(['store', 'update', 'destroy']);
    Route::get('/product-flash-sale-info', [FlashSaleController::class, 'getFlashSaleInfoByProductID']);

    // Terms & conditions (store, update, delete)
    Route::apiResource('/terms-and-conditions', TermsAndConditionsController::class)->only(['store', 'update', 'destroy']);

    // Coupon (store & destroy)
    Route::apiResource('/coupons', CouponController::class)->only(['store', 'destroy']);

    // Vendor list
    Route::get('/vendors/list', [UserManagementController::class, 'vendors']);

    // Ownership transfer (index, show)
    Route::apiResource('/ownership-transfer', OwnershipTransferController::class)->only(['index', 'show']);
});

// ========================
// SUPER ADMIN ONLY (permission:SUPER_ADMIN)
// ========================
Route::group(['middleware' => ['auth:sanctum', 'email.verified', 'permission:'.Permission::SUPER_ADMIN->value]], function () {
    // Types (full CRUD)
    Route::apiResource('/types', TypeController::class)->only(['store', 'update', 'destroy']);

    // Withdraws (update, delete, approve)
    Route::apiResource('/withdraws', WithdrawController::class)->only(['update', 'destroy']);
    Route::post('/approve-withdraw', [WithdrawController::class, 'approveWithdraw']);

    // Categories (full CRUD)
    Route::apiResource('/categories', CategoryController::class)->only(['store', 'update', 'destroy']);

    // Delivery times (full CRUD)
    Route::apiResource('/delivery-times', DeliveryTimeController::class)->only(['store', 'update', 'destroy']);

    // Languages (full CRUD)
    Route::apiResource('/languages', LanguageController::class)->only(['store', 'update', 'destroy']);

    // Tags (full CRUD)
    Route::apiResource('/tags', TagController::class)->only(['store', 'update', 'destroy']);

    // Refund reasons (full CRUD)
    Route::apiResource('/refund-reasons', RefundReasonController::class)->only(['store', 'update', 'destroy']);

    // Resources (update, delete)
    Route::apiResource('/resources', ResourceController::class)->only(['update', 'destroy']);

    // Reviews (delete)
    Route::apiResource('/reviews', ReviewController::class)->only(['destroy']);

    // Questions (delete)
    Route::apiResource('/questions', QuestionController::class)->only(['destroy']);

    // Feedbacks (update, delete)
    Route::apiResource('/feedbacks', FeedbackController::class)->only(['update', 'destroy']);

    // Abusive reports (full)
    Route::apiResource('/abusive_reports', AbusiveReportController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('/abusive_reports/accept', [AbusiveReportController::class, 'accept']);
    Route::post('/abusive_reports/reject', [AbusiveReportController::class, 'reject']);

    // SettingsController
    Route::apiResource('/settings', SettingsController::class)->only(['store', 'update', 'show']);

    // User management (full via UserManagementController)
    Route::apiResource('/users', UserManagementController::class);
    Route::patch('/users/{id}/toggle-active', [UserManagementController::class, 'toggleActive']);
    Route::patch('/users/{id}/toggle-admin', [UserManagementController::class, 'toggleAdmin']);
    Route::patch('/users/{id}/assign-shop', [UserManagementController::class, 'assignShop']);

    // Authors (update, delete)
    Route::apiResource('/authors', AuthorController::class)->only(['update', 'destroy']);

    // Manufacturers (update, delete)
    Route::apiResource('/manufacturers', ManufacturerController::class)->only(['update', 'destroy']);

    // Tax (full)
    Route::apiResource('/taxes', TaxController::class);

    // Shipping (full)
    Route::apiResource('/shippings', ShippingController::class);

    // Shop approval & new shops
    Route::post('/shops/{shop}/approve', [ShopController::class, 'approveShop']);
    Route::post('/shops/{shop}/disapprove', [ShopController::class, 'disApproveShop']);
    Route::get('/new-shops', [ShopController::class, 'newOrInActiveShops']);

    // Refunds (delete, update)
    Route::apiResource('/refunds', RefundController::class)->only(['destroy', 'update']);

    // Notify logs (delete)
    Route::apiResource('/notify-logs', NotifyLogsController::class)->only(['destroy']);

    // Terms approval
    Route::post('/approve-terms-and-conditions', [TermsAndConditionsController::class, 'approveTerm']);
    Route::post('/disapprove-terms-and-conditions', [TermsAndConditionsController::class, 'disApproveTerm']);

    // Refund policies (store, update, delete)
    Route::resource('/refund-policies', RefundPolicyController::class)->only(['store', 'update', 'destroy']);

    // Coupon approval
    Route::post('/approve-coupon', [CouponController::class, 'approveCoupon']);
    Route::post('/disapprove-coupon', [CouponController::class, 'disApproveCoupon']);

    // Flash sale request approval
    Route::post('/approve-flash-sale-requested-products', [FlashSaleRequestController::class, 'approveFlashSaleProductsRequest']);
    Route::post('/disapprove-flash-sale-requested-products', [FlashSaleRequestController::class, 'disapproveFlashSaleProductsRequest']);
    Route::apiResource('/vendor-requests-for-flash-sale', FlashSaleRequestController::class)->only(['update']);

    // Ownership transfer (update, delete)
    Route::apiResource('/ownership-transfer', OwnershipTransferController::class)->only(['update', 'destroy']);
});
// ========================
// Additional Module Routes
// ========================

Route::middleware(['auth:sanctum'])->group(function () {
    // Rental Management
    Route::middleware(['permission:super_admin|store_owner|staff'])->group(function () {
        Route::post('/rental/availability', [ProductRentalController::class, 'checkAvailability'])->name('rental.availability');
        Route::get('/rental/blocked-dates/{productId}', [ProductRentalController::class, 'getBlockedDates'])->name('rental.blocked-dates');
    });

    // Import/Export
    Route::middleware(['permission:super_admin|store_owner'])->group(function () {
        Route::post('/import-products', [ProductController::class, 'importProducts'])->name('products.import');
        Route::post('/import-variation-options', [ProductController::class, 'importVariationOptions'])->name('products.import-variations');
        Route::get('/export-products/{shop_id}', [ProductController::class, 'exportProducts'])->name('products.export');
        Route::get('/export-variation-options/{shop_id}', [ProductController::class, 'exportVariableOptions'])->name('products.export-variations');
    });

    // Analytics Routes (Admin only)
    Route::middleware(['permission:super_admin'])->prefix('admin')->group(function () {
        Route::get('/low-stock-products', [ProductMetricController::class, 'lowStock'])->name('admin.products.low-stock');
        Route::get('/category-wise-product', [ProductMetricController::class, 'categoryWiseProduct'])->name('admin.products.category-wise');
        Route::get('/category-wise-product-sale', [ProductMetricController::class, 'categoryWiseProductSale'])->name('admin.products.category-wise-sale');
        Route::get('/top-rate-product', [ProductMetricController::class, 'topRatedProducts'])->name('admin.products.top-rated');

        // Flash Sale Products
        Route::get('/requested-products-for-flash-sale', [ProductMetricController::class, 'getRequestedProductsForFlashSale'])->name('admin.flash-sale.requested-products');
        Route::post('/approve-flash-sale-requested-products', [ProductMetricController::class, 'approveFlashSaleProductsRequest'])->name('admin.flash-sale.approve');
        Route::post('/disapprove-flash-sale-requested-products', [ProductMetricController::class, 'disapproveFlashSaleProductsRequest'])->name('admin.flash-sale.disapprove');
        Route::get('/product-flash-sale-info', [ProductMetricController::class, 'getFlashSaleInfoByProductID'])->name('admin.flash-sale.info');
    });
});

// Public tags (read-only)
Route::get('/public-tags', [TagController::class, 'index'])->name('tags.public.index');
Route::get('/public-tags/{param}', [TagController::class, 'show'])->name('tags.public.show');

// Public reviews (read-only)
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index'])->name('products.reviews.index');

// Publicly accessible settings (read-only for general info)
Route::get('/public-settings', [SettingsController::class, 'index'])->name('settings.public');
