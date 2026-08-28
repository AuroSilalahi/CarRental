<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentAlreadyPaidException;
use App\Jobs\SendPaymentConfirmationEmail;
use App\Models\Rental;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Display the payment page for a rental.
     *
     * Requirements: 8.1
     */
    public function show(Rental $rental): View
    {
        if (auth()->id() !== $rental->customer_id) {
            abort(403);
        }

        $rental->load('car', 'payment');

        // Initiate payment record if it doesn't exist yet
        if ($rental->payment === null) {
            $this->paymentService->initiatePayment($rental);
            $rental->refresh()->load('car', 'payment');
        }

        $days = $rental->start_date->diffInDays($rental->end_date);
        $days = max(1, $days);

        return view('payments.show', compact('rental', 'days'));
    }

    /**
     * Process payment proof submission.
     */
    public function submitProof(Request $request, Rental $rental): RedirectResponse
    {
        if (auth()->id() !== $rental->customer_id) {
            abort(403);
        }

        $request->validate([
            'payment_method' => 'required|string|max:50',
            'transaction_reference' => 'nullable|string|max:100',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $disk = config('filesystems.default', 'local');
            $path = $request->file('proof_file')->store("payments/proofs/{$rental->id}", $disk);

            $this->paymentService->submitPaymentProof($rental, [
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference,
                'proof_path' => $path,
            ]);

            return redirect()
                ->route('payments.show', $rental->id)
                ->with('success', 'Bukti pembayaran berhasil diunggah! Mohon menunggu konfirmasi Admin.');
        } catch (PaymentAlreadyPaidException $e) {
            return redirect()
                ->back()
                ->with('error', 'Rental ini telah lunas dibayar.');
        }
    }

    /**
     * Process the payment for a rental.
     *
     * Requirements: 8.2, 8.3, 8.4
     */
    public function pay(Rental $rental): RedirectResponse
    {
        if (auth()->id() !== $rental->customer_id) {
            abort(403);
        }

        try {
            $this->paymentService->recordPayment($rental, [
                'payment_method' => 'manual',
            ]);

            SendPaymentConfirmationEmail::dispatch($rental->fresh(['customer', 'car', 'payment']));

            return redirect()
                ->route('bookings.show', $rental->id)
                ->with('success', 'Pembayaran berhasil! Rental Anda telah dikonfirmasi.');
        } catch (PaymentAlreadyPaidException $e) {
            return redirect()
                ->back()
                ->with('error', 'Rental ini sudah dibayar sebelumnya.');
        }
    }
}
