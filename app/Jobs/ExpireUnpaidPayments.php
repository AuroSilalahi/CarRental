<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();

        $payments = Payment::whereIn('status', [PaymentStatus::Unpaid, PaymentStatus::Pending])
            ->where('expires_at', '<=', $now)
            ->with(['rental'])
            ->get();

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        foreach ($payments as $payment) {
            try {
                $paymentService->expirePayment($payment);
            } catch (\Throwable $e) {
                Log::error("Failed expiring payment #{$payment->id}: {$e->getMessage()}");
            }
        }
    }
}
