<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'status',
        'admin_note', 'handled_at', 'handled_by', 'replied_at', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'handled_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ContactMessage $m) => $m->status ??= ContactMessageStatus::Nouveau);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /* ---------------- Scopes ---------------- */

    public function scopeInbox(Builder $query): Builder
    {
        return $query->whereIn('status', [ContactMessageStatus::Nouveau, ContactMessageStatus::Lu]);
    }

    public static function unreadCount(): int
    {
        return static::where('status', ContactMessageStatus::Nouveau)->count();
    }

    /* ---------------- Transitions ---------------- */

    public function markRead(): void
    {
        if ($this->status === ContactMessageStatus::Nouveau) {
            $this->update(['status' => ContactMessageStatus::Lu]);
        }
    }

    public function markHandled(User $admin, bool $replied = false): void
    {
        $this->update([
            'status' => ContactMessageStatus::Traite,
            'handled_by' => $admin->id,
            'handled_at' => now(),
            'replied_at' => $replied ? now() : $this->replied_at,
        ]);
    }

    public function markSpam(User $admin): void
    {
        $this->update([
            'status' => ContactMessageStatus::Spam,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => ContactMessageStatus::Lu,
            'handled_by' => null,
            'handled_at' => null,
        ]);
    }

    /* ---------------- Liens ---------------- */

    public function whatsappLink(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->phone);
        if ($number === '') {
            return null;
        }
        if (strlen($number) <= 10) {
            $number = '225'.ltrim($number, '0');
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(
            "Bonjour {$this->name}, suite à votre message sur Le Roi du Manioc"
            .($this->subject ? " (« {$this->subject} »)" : '').' :'
        );
    }

    public function mailtoLink(): string
    {
        $subject = 'Re : '.($this->subject ?: 'votre message');

        return 'mailto:'.$this->email.'?subject='.rawurlencode($subject);
    }

    public function excerpt(int $length = 140): string
    {
        return Str::limit($this->message, $length);
    }
}
