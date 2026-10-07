# Dokumentasi API E-commerce

Dokumentasi ini menjelaskan endpoint-endpoint API yang tersedia untuk aplikasi e-commerce.

## Dokumentasi Interaktif (API Reference & Playground)

Aplikasi ini menyediakan dua opsi antarmuka dokumentasi API interaktif modern:

1. **Scalar API Reference (Direkomendasikan - Modern & Elegan):**
   - URL: `http://localhost:8000/docs/scalar` (atau `http://localhost:8000/scalar`)
   - Fitur: UI modern yang responsif, tema gelap/terang, code generator untuk berbagai bahasa (cURL, JavaScript, Python, PHP, Go, dll.), serta integrasi API client interaktif.

2. **Scramble / Swagger UI (Bawaan):**
   - URL: `http://localhost:8000/docs/api`

3. **OpenAPI Schema (JSON Spec):**
   - URL: `http://localhost:8000/docs/api.json` (atau file lokal `api.json`)

---

## Standar Format Response

### 1. Success Response
```json
{
  "status": "SUCCESS",
  "success": true,
  "code": 200,
  "message": "Success",
  "data": { ... },
  "timestamp": "2026-10-06T14:11:53.996806Z"
}
```

### 2. Paginated Response
```json
{
  "status": "SUCCESS",
  "success": true,
  "code": 200,
  "message": "Data berhasil diambil.",
  "data": [ ... ],
  "meta": {
    "currentPage": 1,
    "totalPages": 10,
    "totalItems": 100,
    "itemsPerPage": 10
  },
  "timestamp": "2026-10-06T14:11:53.996806Z"
}
```

### 3. Error Response (Standard Enterprise Format)
Semua error (baik dari controller maupun exception global sistem) mengembalikan format seragam berikut:
```json
{
  "status": "ERROR",
  "code": 401,
  "error_code": "API-ECMXS40107",
  "message": "Username, password, atau captcha salah",
  "path": "/api/login",
  "timestamp": "2026-10-06T14:11:53.996806Z"
}
```

Jika terjadi error validasi (HTTP 422), rincian field yang salah akan disertakan di properti `errors`:
```json
{
  "status": "ERROR",
  "code": 422,
  "error_code": "API-ERR-422",
  "message": "The email field is required.",
  "path": "/api/login",
  "timestamp": "2026-10-06T14:11:53.996806Z",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

---

## 1. Public Routes (Tidak Memerlukan Autentikasi)

### Autentikasi
- `POST /register`
  - Registrasi pengguna baru.
- `POST /login`
  - Login pengguna dan mendapatkan token otorisasi.
- `POST /social-login`
  - Login menggunakan akun sosial.
- `POST /password/forgot`
  - Meminta reset password.
- `POST /password/reset`
  - Reset password.

### Produk
- `GET /popular-products`
  - Mendapatkan daftar produk populer.
- `GET /best-selling-products`
  - Mendapatkan daftar produk terlaris.
- `GET /products`
  - Mendapatkan daftar semua produk.
- `GET /products/{id}`
  - Mendapatkan detail produk berdasarkan ID.
- `GET /products/search`
  - Mencari produk.
- `GET /check-availability`
  - Memeriksa ketersediaan produk rental.
- `GET /products/calculate-rental-price`
  - Menghitung harga sewa produk rental.

### Kategori
- `GET /categories`
  - Mendapatkan daftar semua kategori.
- `GET /categories/{id}`
  - Mendapatkan detail kategori berdasarkan ID.
- `GET /featured-categories`
  - Mendapatkan daftar kategori unggulan.

### Toko
- `GET /shops`
  - Mendapatkan daftar semua toko.
- `GET /shops/{slug}`
  - Mendapatkan detail toko berdasarkan slug.
- `GET /near-by-shop`
  - Mendapatkan toko di sekitar lokasi pengguna.

### Lain-lain
- `GET /authors`
- `GET /manufacturers`
- `GET /types`
- `GET /attachments`
- `GET /delivery-times`
- `GET /languages`
- `GET /tags`
- `GET /refund-reasons`
- `GET /resources`
- `POST /coupons/verify`
  - Memverifikasi kupon.
- `GET /attributes`
- `GET /settings`
- `GET /reviews`
- `GET /questions`
- `GET /feedbacks`
- `POST /orders/checkout/verify`
  - Memverifikasi checkout pesanan.
- `GET /orders/track/{identifier}`
  - Melacak status pesanan (untuk guest).
- `GET /payment-intent`
- `GET /faqs`
- `GET /terms-and-conditions`
- `GET /flash-sale`
- `GET /refund-policies`
- `GET /store-notices`
- `GET /download_url/token/{token}`
- `POST /became-seller`

---

## 2. Customer Routes (Memerlukan Autentikasi & Verifikasi Email)

### Profil
- `POST /logout`
  - Logout pengguna.
- `GET /me`
  - Mendapatkan detail profil pengguna yang sedang login.
- `PUT /me`
  - Memperbarui detail profil.
- `POST /me/avatar`
  - Mengunggah avatar.
- `DELETE /me/avatar`
  - Menghapus avatar.

### Keamanan
- `POST /me/change-password`
  - Mengubah password.
- `POST /me/logout-all`
  - Logout dari semua perangkat.
- `GET /me/sessions`
  - Melihat sesi aktif.
- `DELETE /me/sessions/{sessionId}`
  - Mencabut sesi.

### Pesanan
- `GET /my-orders`
  - Mendapatkan daftar pesanan pengguna.
- `POST /orders`
  - Membuat pesanan baru.
- `GET /orders/{identifier}`
  - Mendapatkan detail pesanan berdasarkan ID.
- `POST /orders/{id}/cancel`
  - Membatalkan pesanan.

### Ulasan & Pertanyaan
- `POST /reviews`
  - Membuat ulasan baru.
- `PUT /reviews/{id}`
  - Memperbarui ulasan.
- `POST /questions`
  - Mengajukan pertanyaan baru.
- `GET /my-questions`
  - Melihat pertanyaan yang diajukan.

### Daftar Keinginan (Wishlist)
- `POST /wishlists/toggle`
  - Menambah/menghapus produk dari wishlist.
- `GET /wishlists`
  - Melihat wishlist.
- `GET /wishlists/in_wishlist/{product_id}`
  - Memeriksa apakah produk ada di wishlist.
- `GET /my-wishlists`
  - Melihat produk di wishlist.

### Lain-lain
- `POST /feedbacks`
  - Mengirim feedback.
- `POST /abusive_reports`
  - Melaporkan konten yang tidak pantas.
- `GET /my-reports`
  - Melihat laporan yang dibuat.
- `GET /conversations`
- `POST /conversations`
- `GET /conversations/{conversation_id}`
- `GET /messages/conversations/{conversation_id}`
- `POST /messages/conversations/{conversation_id}`
- `POST /attachments`
- `PUT /attachments/{id}`
- `DELETE /attachments/{id}`
- `POST /addresses`
- `PUT /addresses/{id}`
- `DELETE /addresses/{id}`
- `GET /refunds`
- `POST /refunds`
- `GET /refunds/{id}`
- `GET /downloads`
- `POST /downloads/digital_file`
- `GET /followed-shops`
- `GET /follow-shop`
- `POST /follow-shop`
- `GET /followed-shops-popular-products`
- `GET /payment-methods`
- `POST /payment-methods`
- `GET /payment-methods/gateways`
- `POST /payment-methods/save`
- `POST /payment-methods/setup-intent`
- `POST /payment-methods/set-default`
- `GET /notify-logs`
- `POST /notify-log-seen`
- `POST /notify-log-read-all`

---

## 3. Staff & Store Owner Routes (Memerlukan Autentikasi & Verifikasi Email)

### Produk
- `POST /products`
  - Membuat produk baru.
- `PUT /products/{id}`
  - Memperbarui produk.
- `DELETE /products/{id}`
  - Menghapus produk.
- `GET /draft-products`
  - Mendapatkan daftar produk dalam draft.
- `GET /products-stock`
  - Mendapatkan informasi stok produk.
- `GET /products-by-flash-sale`
  - Mendapatkan produk berdasarkan flash sale.

### Pesanan
- `GET /orders`
  - Mendapatkan semua pesanan.
- `PATCH /orders/{id}/status`
  - Memperbarui status pesanan.
- `PATCH /orders/{id}/payment-status`
  - Memperbarui status pembayaran pesanan.
- `GET /shops/{shopId}/orders`
  - Mendapatkan pesanan berdasarkan toko.
- `GET /orders/stats`
  - Mendapatkan statistik pesanan.

### Lain-lain
- `POST /resources`
- `POST /attributes`
- `PUT /attributes/{id}`
- `DELETE /attributes/{id}`
- `POST /attribute-values`
- `PUT /attribute-values/{id}`
- `DELETE /attribute-values/{id}`
- `PUT /questions/{id}`
- `POST /authors`
- `POST /manufacturers`
- `GET /store-notices/getStoreNoticeType`
- `GET /store-notices/getUsersToNotify`
- `POST /store-notices/read/`
- `POST /store-notices/read-all`
- `GET /store-notices/{id}`
- `POST /store-notices`
- `PUT /store-notices/{id}`
- `DELETE /store-notices/{id}`
- `POST /faqs`
- `PUT /faqs/{id}`
- `DELETE /faqs/{id}`
- `GET /analytics`
- `GET /low-stock-products`
- `GET /category-wise-product`
- `GET /category-wise-product-sale`
- `GET /top-rate-product`
- `PUT /coupons/{id}`

---

## 4. Store Owner Routes (Memerlukan Autentikasi & Verifikasi Email)

### Toko
- `POST /shops`
  - Membuat toko baru.
- `PUT /shops/{shop}`
  - Memperbarui toko.
- `DELETE /shops/{shop}`
  - Menghapus toko.
- `GET /my-shops`
  - Melihat toko yang dimiliki.
- `POST /transfer-shop-ownership`
  - Mentransfer kepemilikan toko.
- `POST /shops/{shop}/staffs`
  - Menambah staf ke toko.
- `DELETE /staffs/{staff}`
  - Menghapus staf.
- `POST /shops/{shop}/maintenance`
  - Mengelola event maintenance toko.

### Lain-lain
- `POST /withdraws`
- `GET /withdraws`
- `GET /withdraws/{id}`
- `POST /flash-sale`
- `PUT /flash-sale/{id}`
- `DELETE /flash-sale/{id}`
- `GET /product-flash-sale-info`
- `POST /terms-and-conditions`
- `PUT /terms-and-conditions/{id}`
- `DELETE /terms-and-conditions/{id}`
- `POST /coupons`
- `DELETE /coupons/{id}`
- `GET /vendors/list`
- `GET /ownership-transfer`
- `GET /ownership-transfer/{id}`

---

## 5. Super Admin Routes (Memerlukan Autentikasi & Verifikasi Email)

### Manajemen Umum
- `POST /types`
- `PUT /types/{id}`
- `DELETE /types/{id}`
- `PUT /withdraws/{id}`
- `DELETE /withdraws/{id}`
- `POST /approve-withdraw`
- `POST /categories`
- `PUT /categories/{id}`
- `DELETE /categories/{id}`
- `POST /delivery-times`
- `PUT /delivery-times/{id}`
- `DELETE /delivery-times/{id}`
- `POST /languages`
- `PUT /languages/{id}`
- `DELETE /languages/{id}`
- `POST /tags`
- `PUT /tags/{id}`
- `DELETE /tags/{id}`
- `POST /refund-reasons`
- `PUT /refund-reasons/{id}`
- `DELETE /refund-reasons/{id}`
- `PUT /resources/{id}`
- `DELETE /resources/{id}`
- `DELETE /reviews/{id}`
- `DELETE /questions/{id}`
- `PUT /feedbacks/{id}`
- `DELETE /feedbacks/{id}`
- `GET /abusive_reports`
- `GET /abusive_reports/{id}`
- `PUT /abusive_reports/{id}`
- `DELETE /abusive_reports/{id}`
- `POST /abusive_reports/accept`
- `POST /abusive_reports/reject`
- `POST /settings`
- `GET /users`
- `POST /users`
- `GET /users/{id}`
- `PUT /users/{id}`
- `DELETE /users/{id}`
- `PATCH /users/{id}/toggle-active`
- `PATCH /users/{id}/toggle-admin`
- `PATCH /users/{id}/assign-shop`
- `PUT /authors/{id}`
- `DELETE /authors/{id}`
- `PUT /manufacturers/{id}`
- `DELETE /manufacturers/{id}`
- `GET /taxes`
- `POST /taxes`
- `GET /taxes/{id}`
- `PUT /taxes/{id}`
- `DELETE /taxes/{id}`
- `GET /shippings`
- `POST /shippings`
- `GET /shippings/{id}`
- `PUT /shippings/{id}`
- `DELETE /shippings/{id}`
- `POST /shops/{shop}/approve`
- `POST /shops/{shop}/disapprove`
- `GET /new-shops`
- `DELETE /refunds/{id}`
- `PUT /refunds/{id}`
- `DELETE /notify-logs/{id}`
- `POST /approve-terms-and-conditions`
- `POST /disapprove-terms-and-conditions`
- `POST /refund-policies`
- `PUT /refund-policies/{id}`
- `DELETE /refund-policies/{id}`
- `POST /approve-coupon`
- `POST /disapprove-coupon`
- `POST /approve-flash-sale-requested-products`
- `POST /disapprove-flash-sale-requested-products`
- `PUT /vendor-requests-for-flash-sale/{id}`
- `PUT /ownership-transfer/{id}`
- `DELETE /ownership-transfer/{id}`
