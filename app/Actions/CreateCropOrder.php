<?php

namespace App\Actions;

use App\Enums\CropOrderStatus;
use App\Events\CropOrderCreated;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique pour passer une commande structurée sur une offre producteur —
 * même esprit que App\Actions\CreateConnectionRequest, mais pour le parcours de commande
 * (§1/§2), délibérément séparé du système de mise en relation par chat.
 *
 * Sécurité :
 * - compte suspendu bloqué ;
 * - rate limit par utilisateur ;
 * - pas de commande déjà OUVERTE (hors refusée/annulée/livrée) sur la même offre pour le
 *   même acheteur.
 */
class CreateCropOrder
{
    private const OPEN_STATUSES_EXCLUDED = [
        CropOrderStatus::Refusee,
        CropOrderStatus::Annulee,
        CropOrderStatus::Livree,
    ];

    public function handle(
        User $buyer,
        CropOffer $offer,
        float $quantity,
        string $unit,
        string $deliveryLocation,
        ?\DateTimeInterface $desiredDate,
        ?string $deliveryNotes,
        string $paymentMethod,
        ?string $qualityExpected,
        ?string $buyerMessage,
    ): CropOrder {
        abort_unless($buyer->isActive(), 403, 'Compte inactif.');

        $key = 'crop-order:'.$buyer->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'general' => 'Trop de commandes envoyées. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        abort_unless(CropOffer::published()->whereKey($offer->id)->exists(), 404);
        abort_if($offer->producerProfile->user_id === $buyer->id, 403);

        $buyerProfile = $buyer->buyerProfile;
        abort_unless($buyerProfile, 403);

        $this->guardAgainstDuplicate($buyerProfile->id, $offer->id);

        $cropOrder = CropOrder::create([
            'crop_offer_id' => $offer->id,
            'producer_profile_id' => $offer->producer_profile_id,
            'buyer_profile_id' => $buyerProfile->id,
            'product_name' => $offer->product_name,
            'variety' => $offer->variety,
            'requested_quantity' => $quantity,
            'requested_unit' => $unit,
            'delivery_location' => $deliveryLocation,
            'desired_date' => $desiredDate,
            'delivery_notes' => $deliveryNotes,
            'payment_method' => $paymentMethod,
            'quality_expected' => $qualityExpected,
            'buyer_message' => $buyerMessage,
            'status' => CropOrderStatus::EnAttenteProducteur,
        ]);

        event(new CropOrderCreated($cropOrder));

        return $cropOrder;
    }

    private function guardAgainstDuplicate(int $buyerProfileId, int $cropOfferId): void
    {
        $exists = CropOrder::query()
            ->where('buyer_profile_id', $buyerProfileId)
            ->where('crop_offer_id', $cropOfferId)
            ->whereNotIn('status', self::OPEN_STATUSES_EXCLUDED)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'general' => 'Une commande est déjà en cours entre vous pour ce produit.',
            ]);
        }
    }
}
