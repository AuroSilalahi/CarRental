<?php

namespace App\Enums;

enum IdentityDocumentStatus: string
{
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
