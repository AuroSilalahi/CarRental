<?php

namespace App\Enums;

enum RentalStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case ReviewRequired = 'review_required';
}
