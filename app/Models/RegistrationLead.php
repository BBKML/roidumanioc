<?php

namespace App\Models;

use App\Enums\RegistrationLeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationLead extends Model
{
    protected $fillable = [
        'registration_form_id', 'gender', 'last_name', 'first_name', 'email',
        'phone_1', 'phone_2', 'whatsapp', 'country_code', 'experience_level', 'age_range',
        'profession', 'company', 'city_country', 'motivations', 'expectations', 'how_heard',
        'payment_method', 'payment_frequency', 'status', 'admin_note', 'handled_by',
        'handled_at', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationLeadStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (RegistrationLead $lead) => $lead->status ??= RegistrationLeadStatus::Nouveau);
    }

    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /* ---------------- Transitions ---------------- */

    public function markContacted(User $admin): void
    {
        $this->update([
            'status' => RegistrationLeadStatus::Contacte,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);
    }

    public function markEnrolled(User $admin): void
    {
        $this->update([
            'status' => RegistrationLeadStatus::Inscrit,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);
    }

    public function markAbandoned(User $admin): void
    {
        $this->update([
            'status' => RegistrationLeadStatus::Abandonne,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => RegistrationLeadStatus::Nouveau,
            'handled_by' => null,
            'handled_at' => null,
        ]);
    }

    /* ---------------- Liens ---------------- */

    public function whatsappLink(): ?string
    {
        $number = preg_replace('/\D+/', '', $this->whatsapp ?: (string) $this->phone_1);
        if ($number === '') {
            return null;
        }
        if (strlen($number) <= 10) {
            $number = '225'.ltrim($number, '0');
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(
            "Bonjour {$this->first_name}, suite à votre inscription à « {$this->registrationForm->title} » :"
        );
    }

    public function mailtoLink(): ?string
    {
        if (! $this->email) {
            return null;
        }

        return 'mailto:'.$this->email.'?subject='.rawurlencode('Votre inscription — '.$this->registrationForm->title);
    }
}
