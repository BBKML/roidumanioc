<?php

namespace App\Models;

use App\Support\ContactDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'type', 'body',
        'proposal_terms', 'contains_flagged_content', 'flagged_patterns',
    ];

    protected function casts(): array
    {
        return [
            'contains_flagged_content' => 'boolean',
            'flagged_patterns' => 'array',
            'proposal_terms' => 'array',
        ];
    }

    public function isProposal(): bool
    {
        return $this->type === 'proposition';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * `body` reste toujours le texte brut (traçabilité admin) ; c'est cette méthode qui
     * masque les coordonnées détectées pour l'affichage aux deux parties. `$unmasked`
     * (écran de modération admin) renvoie le texte tel quel.
     */
    public function displayBody(bool $unmasked = false): string
    {
        if ($unmasked || ! $this->contains_flagged_content) {
            return $this->body;
        }

        return ContactDetector::mask($this->body, $this->flagged_patterns ?? []);
    }
}
