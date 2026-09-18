<?php

namespace Tests\Feature\Mail;

use App\Mail\OrderCreated as OrderCreatedMailable; // Alias untuk menghindari konflik nama
use App\Models\Order;
use App\Models\User;
use App\Modules\Order\Events\OrderCreated as OrderCreatedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCreatedMailTest extends TestCase
{
    use RefreshDatabase; // Gunakan ini jika Anda ingin database di-refresh untuk setiap test

    public function test_order_created_event_sends_email_to_customer()
    {
        Mail::fake(); // Mengaktifkan Mail fake facade

        // 1. Setup: Buat customer dan order menggunakan factory
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'name' => 'John Doe',
        ]);
        $order = Order::factory()->for($customer, 'customer')->create([
            'tracking_number' => 'ORD-TEST-001',
            'paid_total' => 150.00,
            'language' => 'en', // Pastikan order memiliki bahasa
            // Tambahkan data order lain yang relevan jika dibutuhkan oleh email template
        ]);

        // Pastikan relasi 'customer' dimuat jika dibutuhkan oleh listener/mailable
        // OrderCreated event di api-ecommerce-00 membutuhkan Order object
        // dan listener-nya SendOrderCreationNotification menggunakan $order->customer
        // jadi pastikan relasi 'customer' ada.
        $order->load('customer');

        // 2. Action: Dispatch event OrderCreated
        event(new OrderCreatedEvent($order, []));

        // 3. Assertions: Verifikasi email terkirim
        Mail::assertQueued(OrderCreatedMailable::class, function ($mail) use ($customer, $order) {
            // Periksa apakah email dikirim ke customer yang benar
            $this->assertTrue($mail->hasTo($customer->email));

            // Periksa apakah mailable menerima instance order yang benar
            $this->assertTrue($mail->order->is($order));

            // Periksa subjek email (gunakan __() untuk memicu terjemahan)
            $expectedSubject = __('order.created_subject', ['ORDER_TRACKING_NUMBER' => $order->tracking_number], $order->language);
            $this->assertTrue($mail->hasSubject($expectedSubject));

            // Opsional: Render email untuk memeriksa konten body (hati-hati dengan konten dinamis)
            // $renderedMail = $mail->render();
            // $this->assertStringContainsString($order->tracking_number, $renderedMail);
            // $this->assertStringContainsString((string) $order->paid_total, $renderedMail);

            return true;
        });
    }
}
