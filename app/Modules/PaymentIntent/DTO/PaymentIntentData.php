<?php

declare(strict_types=1);

namespace App\Modules\PaymentIntent\DTO;

final class PaymentIntentData
{
    public function __construct(
        public readonly string $tracking_number,
        public readonly string $payment_gateway,
        public readonly ?bool $recall_gateway = false,
    ) {}

    /**
     * @param  array{tracking_number: scalar, payment_gateway: scalar, recall_gateway?: bool}  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            tracking_number: (string) $data['tracking_number'],
            payment_gateway: (string) $data['payment_gateway'],
            recall_gateway: isset($data['recall_gateway']) ? (bool) $data['recall_gateway'] : false,
        );
    }
}
