<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * Transform the Car model into a camelCase JSON representation.
     *
     * Requirements: 6.2, 6.3, 6.5, 6.6
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'brand'             => $this->brand,
            'model'             => $this->model,
            'type'              => $this->type,
            'licensePlate'      => $this->license_plate,
            'passengerCapacity' => $this->passenger_capacity,
            'colour'            => $this->colour,
            'year'              => $this->year,
            'dailyRateIdr'      => $this->daily_rate_idr,
            'isAvailable'       => $this->is_available,
            'isLuxuryBrand'     => $this->is_luxury_brand,
            'luxuryMultiplier'  => $this->luxury_multiplier,
            'imagePath'         => $this->image_path,
            'createdAt'         => $this->created_at,
            'updatedAt'         => $this->updated_at,
        ];
    }
}
