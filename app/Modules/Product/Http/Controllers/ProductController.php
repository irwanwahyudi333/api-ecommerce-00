<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Variation;
use App\Modules\Product\Actions\CreateProductAction;
use App\Modules\Product\Actions\DeleteProductAction;
use App\Modules\Product\Actions\UpdateProductAction;
use App\Modules\Product\DTO\ProductData;
use App\Modules\Product\Http\Requests\ProductCreateRequest;
use App\Modules\Product\Http\Requests\ProductUpdateRequest;
use App\Modules\Product\Http\Resources\GetSingleProductResource;
use App\Modules\Product\Http\Resources\ProductResource;
use App\Modules\Product\Services\ProductMetricService;
use App\Modules\Product\Services\ProductRentalService;
use App\Modules\Product\Services\ProductService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends BaseController
{
    public function __construct(
        private ProductService $productService,
        private ProductMetricService $productMetricService,
        private ProductRentalService $productRentalService,
        private CreateProductAction $createProductAction,
        private UpdateProductAction $updateProductAction,
        private DeleteProductAction $deleteProductAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limitParam = $request->limit ?? 15;
        $limit = is_numeric($limitParam) ? (int) $limitParam : 15;
        $cacheKey = 'products_'.md5($request->fullUrl());

        /** @var LengthAwarePaginator<int, Product> $products */
        $products = Cache::remember($cacheKey, 300, function () use ($request, $limit) {
            return $this->productService->getProducts($request, $limit);
        });

        return $this->sendPaginated(
            $products,
            ProductResource::collection($products->getCollection()),
            'Daftar produk berhasil diambil.'
        );
    }

    public function store(ProductCreateRequest $request): JsonResponse
    {
        $this->authorize('create', [Product::class, $request->shop_id]);
        $settings = Settings::first();
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = ProductData::fromRequest($validated);
        $product = $this->createProductAction->execute($data, $settings ?? (object) []);
        Cache::forget('products_*');

        return $this->sendSuccess(
            new ProductResource($product->load(['type', 'shop'])),
            'Product created',
            201
        );
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $language = is_string($request->language) ? $request->language : 'id';
            $cacheKey = 'product_detail_'.$slug.'_'.$language;
            $product = Cache::remember($cacheKey, 600, function () use ($request, $slug) {
                return $this->productService->getProductDetail($request, $slug);
            });

            return $this->sendSuccess(
                new GetSingleProductResource($product),
                'Product detail'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Product not found', 404);
        }
    }

    public function update(ProductUpdateRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);
        $settings = Settings::first();
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = ProductData::fromRequest($validated);
        $updated = $this->updateProductAction->execute($product, $data, $settings ?? (object) []);
        Cache::forget('product_detail_'.$product->slug.'_*');
        Cache::forget('products_*');

        return $this->sendSuccess(
            new ProductResource($updated->load(['type', 'shop', 'categories', 'tags'])),
            'Product updated'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);
        $slug = $product->slug;
        $this->deleteProductAction->execute($product);
        Cache::forget('product_detail_'.$slug.'_*');
        Cache::forget('products_*');

        return $this->sendSuccess(null, 'Product deleted successfully');
    }

    public function relatedProducts(Request $request): JsonResponse
    {
        $limitParam = $request->limit ?? 10;
        $limit = is_numeric($limitParam) ? (int) $limitParam : 10;
        $slug = is_string($request->slug) ? $request->slug : '';
        $languageParam = $request->language ?? config('shop.default_language', 'id');
        $language = is_string($languageParam) ? $languageParam : 'id';
        $cacheKey = "related_products_{$slug}_{$language}_{$limit}";
        $products = Cache::remember($cacheKey, 600, function () use ($slug, $limit, $language) {
            $product = Product::where('slug', $slug)->where('language', $language)->firstOrFail();

            return $this->productService->getRelatedProducts($product, $limit, $language);
        });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Related products'
        );
    }

    public function bestSellingProducts(Request $request): JsonResponse
    {
        $cacheKey = 'best_selling_'.md5($request->fullUrl());
        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            return $this->productMetricService->getBestSellingProducts($request);
        });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Best selling products'
        );
    }

    public function popularProducts(Request $request): JsonResponse
    {
        $cacheKey = 'popular_products_'.md5($request->fullUrl());
        $products = Cache::remember($cacheKey, 600, function () use ($request) {
            return $this->productMetricService->getPopularProducts($request);
        });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Popular products'
        );
    }

    public function draftedProducts(Request $request): JsonResponse
    {
        $this->authorize('viewDrafted', Product::class);
        $products = $this->productService->getDraftedProducts($request);

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Drafted products'
        );
    }

    public function productStock(Request $request): JsonResponse
    {
        $this->authorize('viewStock', Product::class);
        $products = $this->productService->getLowStockProducts($request);

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Low stock products'
        );
    }

    public function myWishlists(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }
        $products = $this->productService->getMyWishlists($request);

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Wishlist products'
        );
    }

    public function calculateRentalPrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
            'variation_id' => 'nullable|exists:variation_options,id',
            'quantity' => 'nullable|integer|min:1',
            'pickup_location_id' => 'nullable|exists:resources,id',
        ]);
        $price = $this->productRentalService->calculateRentalPrice($request);

        return $this->sendSuccess($price, 'Rental price calculated');
    }

    public function exportProducts(Request $request, int $shopId): StreamedResponse
    {
        $this->authorize('export', [Product::class, $shopId]);

        $filename = 'products-for-shop-id-'.$shopId.'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($shopId) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            $headers = [
                'name', 'slug', 'price', 'sale_price', 'type_id', 'shop_id',
                'author_id', 'manufacturer_id', 'language', 'product_type',
                'quantity', 'unit', 'is_digital', 'is_external', 'description',
                'sku', 'image', 'gallery', 'video', 'status', 'height',
                'length', 'width', 'in_stock', 'is_taxable', 'visibility',
            ];
            fputcsv($handle, $headers);

            Product::where('shop_id', $shopId)->chunk(100, function ($products) use ($handle) {
                foreach ($products as $product) {
                    $row = [
                        $product->name,
                        $product->slug,
                        $product->price,
                        $product->sale_price,
                        $product->type_id,
                        $product->shop_id,
                        $product->author_id,
                        $product->manufacturer_id,
                        $product->language,
                        $product->product_type,
                        $product->quantity,
                        $product->unit,
                        $product->is_digital ? '1' : '0',
                        $product->is_external ? '1' : '0',
                        $product->description,
                        $product->sku,
                        json_encode($product->image),
                        json_encode($product->gallery),
                        json_encode($product->video),
                        $product->status,
                        $product->height,
                        $product->length,
                        $product->width,
                        $product->in_stock ? '1' : '0',
                        $product->is_taxable ? '1' : '0',
                        $product->visibility,
                    ];
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportVariableOptions(Request $request, int $shopId): StreamedResponse
    {
        $this->authorize('export', [Product::class, $shopId]);

        $filename = 'variable-options-'.Str::random(5).'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($shopId) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            $headers = ['product_id', 'sku', 'title', 'price', 'sale_price', 'quantity', 'options', 'image'];
            fputcsv($handle, $headers);

            $productIds = Product::where('shop_id', $shopId)->pluck('id');
            Variation::whereIn('product_id', $productIds)->chunk(100, function ($variations) use ($handle) {
                foreach ($variations as $variation) {
                    $row = [
                        $variation->product_id,
                        $variation->sku,
                        $variation->title,
                        $variation->price,
                        $variation->sale_price,
                        $variation->quantity,
                        json_encode($variation->options),
                        json_encode($variation->image),
                    ];
                    fputcsv($handle, $row);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importProducts(Request $request): JsonResponse
    {
        $shopId = is_numeric($request->shop_id) ? (int) $request->shop_id : 0;
        $this->authorize('import', [Product::class, $shopId]);

        if (! $request->hasFile('csv')) {
            return $this->sendError('CSV file is required', 422);
        }

        $file = $request->file('csv');
        if (! $file instanceof UploadedFile) {
            return $this->sendError('Invalid file type', 422);
        }
        $path = $file->storeAs('csv-imports', 'products-'.$shopId.'-'.time().'.csv', 'local');
        $csvData = $this->csvToArray(storage_path('app/'.(is_string($path) ? $path : '')));

        if (empty($csvData)) {
            return $this->sendError('CSV file is empty or invalid', 422);
        }

        $settings = Settings::first();
        $total = count($csvData);
        $success = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                /** @var array<string, mixed> $r */
                $r = $row;
                if (empty($r['type_id'])) {
                    throw new \Exception('type_id is required at row '.($index + 1));
                }

                $name = isset($r['name']) && is_string($r['name']) ? $r['name'] : '';
                $slug = isset($r['slug']) && is_string($r['slug']) ? $r['slug'] : Str::slug($name !== '' ? $name : 'product-'.uniqid());
                $langParam = config('shop.default_language', 'id');
                $lang = is_string($langParam) ? $langParam : 'id';

                $data = new ProductData(
                    name: $name,
                    slug: $slug,
                    price: isset($r['price']) && is_numeric($r['price']) ? (float) $r['price'] : 0.0,
                    sale_price: isset($r['sale_price']) && is_numeric($r['sale_price']) ? (float) $r['sale_price'] : null,
                    max_price: isset($r['max_price']) && is_numeric($r['max_price']) ? (float) $r['max_price'] : null,
                    min_price: isset($r['min_price']) && is_numeric($r['min_price']) ? (float) $r['min_price'] : null,
                    type_id: is_numeric($r['type_id']) ? (int) $r['type_id'] : 0,
                    shop_id: $shopId,
                    author_id: isset($r['author_id']) && is_numeric($r['author_id']) ? (int) $r['author_id'] : null,
                    manufacturer_id: isset($r['manufacturer_id']) && is_numeric($r['manufacturer_id']) ? (int) $r['manufacturer_id'] : null,
                    language: isset($r['language']) && is_string($r['language']) ? $r['language'] : $lang,
                    product_type: isset($r['product_type']) && is_string($r['product_type']) ? $r['product_type'] : 'simple',
                    quantity: isset($r['quantity']) && is_numeric($r['quantity']) ? (int) $r['quantity'] : null,
                    unit: isset($r['unit']) && is_string($r['unit']) ? $r['unit'] : null,
                    is_digital: isset($r['is_digital']) ? (bool) $r['is_digital'] : false,
                    is_external: isset($r['is_external']) ? (bool) $r['is_external'] : false,
                    external_product_url: isset($r['external_product_url']) && is_string($r['external_product_url']) ? $r['external_product_url'] : null,
                    external_product_button_text: isset($r['external_product_button_text']) && is_string($r['external_product_button_text']) ? $r['external_product_button_text'] : null,
                    description: isset($r['description']) && is_string($r['description']) ? $r['description'] : null,
                    sku: isset($r['sku']) && is_string($r['sku']) ? $r['sku'] : null,
                    image: isset($r['image']) && is_string($r['image']) ? (array) json_decode($r['image'], true) : null,
                    gallery: isset($r['gallery']) && is_string($r['gallery']) ? (array) json_decode($r['gallery'], true) : null,
                    video: isset($r['video']) && is_string($r['video']) ? (array) json_decode($r['video'], true) : null,
                    status: isset($r['status']) && is_string($r['status']) ? $r['status'] : 'draft',
                    height: isset($r['height']) && is_scalar($r['height']) ? (string) $r['height'] : null,
                    length: isset($r['length']) && is_scalar($r['length']) ? (string) $r['length'] : null,
                    width: isset($r['width']) && is_scalar($r['width']) ? (string) $r['width'] : null,
                    in_stock: isset($r['in_stock']) ? (bool) $r['in_stock'] : true,
                    is_taxable: isset($r['is_taxable']) ? (bool) $r['is_taxable'] : true,
                    sold_quantity: isset($r['sold_quantity']) && is_numeric($r['sold_quantity']) ? (int) $r['sold_quantity'] : 0,
                    visibility: isset($r['visibility']) && is_string($r['visibility']) ? $r['visibility'] : 'public',
                    categories: isset($r['categories']) && is_string($r['categories']) ? (array) json_decode($r['categories'], true) : null,
                    tags: isset($r['tags']) && is_string($r['tags']) ? (array) json_decode($r['tags'], true) : null,
                    dropoff_locations: null,
                    pickup_locations: null,
                    persons: null,
                    features: null,
                    deposits: null,
                    metas: null,
                    variations: null,
                    variation_options: null,
                    digital_file: null,
                    inform_purchased_customer: false,
                    product_update_message: null,
                    is_rental: isset($r['is_rental']) ? (bool) $r['is_rental'] : false,
                );

                $this->createProductAction->execute($data, $settings ?? (object) []);
                $success++;
            } catch (\Exception $e) {
                $errors[] = 'Row '.($index + 1).': '.$e->getMessage();
            }
        }

        Cache::forget('products_*');

        return $this->sendSuccess([
            'total' => $total,
            'success' => $success,
            'errors' => $errors,
        ], 'Import completed');
    }

    /**
     * Helper to convert CSV to array
     *
     * @return array<int, array<string, mixed>>
     */
    private function csvToArray(string $filename, string $delimiter = ','): array
    {
        if (! file_exists($filename) || ! is_readable($filename)) {
            return [];
        }
        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    if (count($header) === count($row)) {
                        /** @var array<string> $headerNames */
                        $headerNames = $header;
                        $data[] = array_combine($headerNames, $row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }
}
