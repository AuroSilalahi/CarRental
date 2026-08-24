<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalResource extends JsonResource
{
    /**
     * Transform the Rental model into a camelCase JSON representation.
     *
     * Requirements: 9.1, 9.2, 9.4
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'referenceNumber' => $this->reference_number,
            'carId'           => $this->car_id,
            'customerId'      => $this->customer_id,
            'startDate'       => $this->start_date?->toDateString(),
            'endDate'         => $this->end_date?->toDateString(),
            'pickupLocation'  => $this->pickup_location,
            'returnLocation'  => $this->return_location,
            'totalCostIdr'    => $this->total_cost_idr,
            'status'          => $this->status?->value,
            'car'             => new CarResource($this->whenLoaded('car')),
            'createdAt'       => $this->created_at,
            'updatedAt'       => $this->updated_at,
        ];
    }
}
