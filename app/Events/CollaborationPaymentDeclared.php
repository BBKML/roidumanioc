<?php

namespace App\Events;

use App\Models\Collaboration;

/**
 * Complète le point d'extension laissé par la Phase 7 (LogsActivity existait déjà,
 * aucun event) — branché à la Phase 9. Toujours acheteur -> producteur
 * (Collaboration::declarePayment() est réservée à l'acheteur), aucun acteur à
 * transporter ici, contrairement à CollaborationDeliveryStepMarked.
 */
class CollaborationPaymentDeclared
{
    public function __construct(public Collaboration $collaboration) {}
}
