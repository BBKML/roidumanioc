<?php

namespace App\Events;

use App\Models\ConversationMessage;

/**
 * Complète le point d'extension laissé par la Phase 6 (aucun event n'y était dispatché) —
 * branché à la Phase 9 : notification in-app uniquement, jamais par e-mail (pour ne pas
 * spammer, cf. cahier des charges §24).
 */
class ConversationMessageSent
{
    public function __construct(public ConversationMessage $message) {}
}
