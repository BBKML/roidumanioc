<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** Nombre de messages signalés à partir duquel un badge de modération s'affiche côté admin. */
    public const FLAG_THRESHOLD = 3;

    protected $fillable = ['connection_request_id'];

    public function connectionRequest(): BelongsTo
    {
        return $this->belongsTo(ConnectionRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function flaggedMessagesCount(): int
    {
        return $this->messages()->where('contains_flagged_content', true)->count();
    }

    public function needsModeration(): bool
    {
        return $this->flaggedMessagesCount() >= self::FLAG_THRESHOLD;
    }
}
