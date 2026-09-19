<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderUpdateMail;
use App\Mail\PaymentRejectedMail;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Referme les demandes de paiement restées sans preuve après un délai :
 *  - commandes produits « paiement en attente » de plus de 5 jours ;
 *  - inscriptions premium « paiement » de plus de 7 jours.
 * Le stock est réintégré (event Order) et le client est prévenu qu'il peut refaire une demande.
 * Les dossiers AVEC capture téléversée sont laissés à l'admin.
 */
class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending {--order-days=5} {--enrolment-days=7}';

    protected $description = 'Annule les commandes et inscriptions dont le paiement n\'a jamais été justifié';

    public function handle(): int
    {
        $orders = $this->expireOrders((int) $this->option('order-days'));
        $enrolments = $this->expireEnrolments((int) $this->option('enrolment-days'));

        $this->info("Commandes annulées : {$orders} · inscriptions expirées : {$enrolments}.");

        return self::SUCCESS;
    }

    private function expireOrders(int $days): int
    {
        $count = 0;

        Order::with(['payment', 'user', 'orderable'])
            ->where('status', OrderStatus::Paiement)
            ->where('ordered_at', '<', now()->subDays($days))
            ->whereDoesntHave('payment', fn ($q) => $q->whereNotNull('proof_path'))
            ->each(function (Order $order) use (&$count) {
                $order->payment?->update([
                    'status' => PaymentStatus::Refuse,
                    'rejection_reason' => 'Paiement non justifié dans les délais.',
                ]);
                $order->cancel();
                $this->safeMail(fn () => $order->user
                    && Mail::to($order->user->email)->send(new OrderUpdateMail($order->fresh(), 'cancelled')));
                $count++;
            });

        return $count;
    }

    private function expireEnrolments(int $days): int
    {
        $count = 0;

        Enrollment::with(['payment', 'user'])
            ->where('status', EnrollmentStatus::Paiement)
            ->where('created_at', '<', now()->subDays($days))
            ->whereDoesntHave('payment', fn ($q) => $q->whereNotNull('proof_path'))
            ->each(function (Enrollment $enrolment) use (&$count) {
                $payment = $enrolment->payment;
                $payment?->update([
                    'status' => PaymentStatus::Refuse,
                    'rejection_reason' => 'Paiement non justifié dans les délais. Vous pouvez refaire une demande.',
                ]);
                $enrolment->update(['status' => EnrollmentStatus::Refuse]);

                if ($payment) {
                    $this->safeMail(fn () => Mail::to($enrolment->user->email)->send(new PaymentRejectedMail($payment->fresh())));
                }
                $count++;
            });

        return $count;
    }

    private function safeMail(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
