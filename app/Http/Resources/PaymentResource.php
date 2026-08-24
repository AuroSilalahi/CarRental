<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the Payment model into a camelCase JSON representation.
     *
     * Requirements: 8.1, 8.2, 8.3, 8.4
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'rentalId'      => $this->rental_id,
            'amountIdr'     => $this->amount_idr,
            'status'        => $this->status?->value,
            'paymentMethod' => $this->payment_method,
            'paidAt'        => $this->paid_at,
            'expiresAt'     => $this->expires_at,
            'createdAt'     => $this->created_at,
            'updatedAt'     => $this->updated_at,
        ];
    }
}
