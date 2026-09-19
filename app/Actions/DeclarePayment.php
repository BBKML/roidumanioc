<?php

namespace App\Actions;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\NewPaymentToVerifyMail;
use App\Mail\PaymentSubmittedMail;
use App\Models\Formation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre une déclaration de paiement manuel (formation ou produit).
 *
 * Sécurité :
 * - un seul paiement « à vérifier » par (client, objet) — anti-spam ;
 * - le compte suspendu ne peut pas soumettre ;
 * - le montant ATTENDU est toujours calculé côté serveur (jamais reçu du client) ;
 * - la capture est stockée sur le disque privé + empreinte SHA-256 conservée ;
 * - le contrôle automatique tourne mais ne débloque jamais rien.
 */
class DeclarePayment
{
    /**
     * @param  Formation|ShopProduct  $payable
     */
    public function handle(
        User $user,
        $payable,
        string $method,
        int $declaredAmount,
        ?string $transactionId,
        UploadedFile $proof,
        int $quantity = 1,
        ?Order $order = null,
    ): Payment {
        abort_unless($user->isActive(), 403, 'Compte inactif.');

        $key = 'declare-payment:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'proof' => 'Trop de tentatives. Réessayez dans un moment ou contactez-nous sur WhatsApp.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        $quantity = max(1, $quantity);
        $expected = match (true) {
            $order !== null => $order->total(),                 // sous-total + frais de livraison
            $payable instanceof Formation => (int) $payable->price,
            default => (int) $payable->price * $quantity,
        };

        $this->guardAgainstDuplicatePending($user, $payable);

        // Stockage de la preuve — disque privé, nom aléatoire, jamais le nom d'origine.
        $hash = hash_file('sha256', $proof->getRealPath());
        $mime = $proof->getMimeType();
        $path = $proof->store('proofs/'.now()->format('Y/m'), 'local');

        $payment = new Payment([
            'user_id' => $user->id,
            'label' => $this->label($payable, $quantity),
            'amount' => $expected,
            'quantity' => $quantity,
            'method' => $method,
            'status' => PaymentStatus::AVerifier,
            'declared_amount' => $declaredAmount,
            'transaction_id' => $transactionId ? trim($transactionId) : null,
            'proof_path' => $path,
            'proof_hash' => $hash,
            'proof_mime' => $mime,
        ]);
        $payment->payable()->associate($payable);

        if ($payable instanceof Formation) {
            $enrollment = $payable->enrollments()->updateOrCreate(
                ['user_id' => $user->id],
                ['status' => EnrollmentStatus::Paiement],
            );
            $payment->enrollment_id = $enrollment->id;
        } else {
            // La commande est créée en amont par ShopCheckout (coordonnées + frais).
            $order ??= $user->orders()->create([
                'customer_name' => $user->name,
                'orderable_type' => $payable->getMorphClass(),
                'orderable_id' => $payable->id,
                'item_label' => $this->label($payable, $quantity),
                'quantity' => $quantity,
                'amount' => $expected,
                'status' => OrderStatus::Paiement,
            ]);
            $payment->order_id = $order->id;
        }

        $payment->save();
        $payment->runAutoCheck();
        $payment->save();

        $this->notify($payment);

        return $payment;
    }

    private function guardAgainstDuplicatePending(User $user, $payable): void
    {
        $exists = Payment::query()
            ->where('user_id', $user->id)
            ->where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->id)
            ->where('status', PaymentStatus::AVerifier)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'proof' => 'Un paiement pour cet article est déjà en cours de vérification. Contactez-nous sur WhatsApp si besoin.',
            ]);
        }
    }

    private function label($payable, int $quantity): string
    {
        if ($payable instanceof Formation) {
            return 'Formation — '.$payable->title;
        }

        return $payable->name.($quantity > 1 ? " ×{$quantity}" : '');
    }

    private function notify(Payment $payment): void
    {
        try {
            Mail::to($payment->user->email)->send(new PaymentSubmittedMail($payment));

            $adminEmail = User::where('role', 'admin')->where('status', 'actif')->value('email')
                ?: config('mail.from.address');
            Mail::to($adminEmail)->send(new NewPaymentToVerifyMail($payment));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
