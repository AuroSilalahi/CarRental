<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentAlreadyPaidException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Jobs\SendPaymentConfirmationEmail;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Return the payment detail for the given rental.
     *
     * GET /api/v1/payments/{rental}
     *
     * Only the rental owner may access this.
     *
     * Requirements: 8.1
     */
    public function show(Request $request, int $rental): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $rentalModel = Rental::find($rental);

        if (! $rentalModel) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Rental tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Authorization: only the rental owner may view payment
        if ($rentalModel->customer_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke pembayaran ini.',
                'data'    => null,
            ], 403);
        }

        $payment = Payment::where('rental_id', $rentalModel->id)->first();

        if (! $payment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pembayaran tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail pembayaran berhasil diambil.',
            'data'    => new PaymentResource($payment),
        ], 200);
    }

    /**
     * Process a payment for the given rental.
     *
     * POST /api/v1/payments/{rental}/pay
     *
     * Only the rental owner may pay.
     *
     * Requirements: 8.2, 8.3, 8.4
     */
    public function pay(Request $request, int $rental): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $rentalModel = Rental::with('car', 'customer')->find($rental);

        if (! $rentalModel) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Rental tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Authorization: only the rental owner may pay
        if ($rentalModel->customer_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke pembayaran ini.',
                'data'    => null,
            ], 403);
        }

        try {
            $payment = $this->paymentService->recordPayment($rentalModel, $request->only('payment_method'));
        } catch (PaymentAlreadyPaidException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }

        // Dispatch payment confirmation email to queue
        SendPaymentConfirmationEmail::dispatch($rentalModel);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pembayaran berhasil. Rental Anda telah dikonfirmasi.',
            'data'    => new PaymentResource($payment),
        ], 200);
    }
}
