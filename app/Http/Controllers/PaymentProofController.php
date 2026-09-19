<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sert la capture de paiement depuis le disque privé.
 * Accessible à l'admin et au propriétaire du paiement uniquement.
 */
class PaymentProofController extends Controller
{
    public function __invoke(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($payment->canBeViewedBy($request->user()), 403);
        abort_unless(
            $payment->proof_path && Storage::disk('local')->exists($payment->proof_path),
            404,
        );

        return Storage::disk('local')->response(
            $payment->proof_path,
            'preuve-'.$payment->reference.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION),
            ['Content-Type' => $payment->proof_mime ?: 'application/octet-stream'],
        );
    }
}
