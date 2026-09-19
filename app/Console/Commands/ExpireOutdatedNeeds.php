<?php

namespace App\Console\Commands;

use App\Enums\BuyerNeedStatus;
use App\Models\BuyerNeed;
use Illuminate\Console\Command;

/**
 * Marque comme « expiré » les besoins acheteur encore ouverts dont la date souhaitée
 * est dépassée. Aucun effet de bord par enregistrement (pas d'e-mail, pas de stock à
 * réintégrer) contrairement à payments:expire-pending → mise à jour en masse.
 */
class ExpireOutdatedNeeds extends Command
{
    protected $signature = 'needs:expire-outdated';

    protected $description = 'Passe en "expiré" les besoins ouverts dont la date souhaitée est dépassée';

    public function handle(): int
    {
        $count = BuyerNeed::where('status', BuyerNeedStatus::Ouvert)
            ->whereNotNull('wanted_date')
            ->whereDate('wanted_date', '<', now()->toDateString())
            ->update(['status' => BuyerNeedStatus::Expire]);

        $this->info("Besoins expirés : {$count}.");

        return self::SUCCESS;
    }
}
