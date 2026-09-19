<?php

namespace App\Models;

use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'formation_id', 'title', 'type', 'duration_label',
        'video_provider', 'video_url', 'video_disk', 'video_path',
        'content', 'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => LessonType::class,
        ];
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('position');
    }

    public function completedBy(User $user): bool
    {
        return LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->exists();
    }

    /* ---------------- Vidéo ---------------- */

    /**
     * Nature du média principal : 'embed' (iframe), 'file' (balise <video>),
     * 'document' (PDF joint affiché en ligne) ou 'none'.
     */
    public function mediaKind(): string
    {
        if ($this->type === LessonType::Document) {
            return $this->primaryDocument() ? 'document' : 'none';
        }

        return match ($this->video_provider) {
            'upload' => filled($this->video_path) ? 'file' : 'none',
            'link', 'bunny' => $this->embedUrl() ? 'embed' : 'none',
            default => 'none',
        };
    }

    /** URL de la balise <video> pour une vidéo téléversée (route gardée). */
    public function videoStreamUrl(): ?string
    {
        return $this->video_provider === 'upload' && filled($this->video_path)
            ? route('lessons.video', $this)
            : null;
    }

    /** Première ressource PDF — contenu principal d'une leçon de type « document ». */
    public function primaryDocument(): ?LessonAttachment
    {
        return $this->attachments->first(fn (LessonAttachment $a) => $a->isPdf());
    }

    /**
     * URL d'intégration (iframe) pour les sources « link » et « bunny ».
     *
     * - Bunny Stream : GUID nu OU URL iframe.mediadelivery.net → embed signé
     *   (non partageable quand `services.bunny.token_key` est défini).
     * - YouTube / Vimeo : converti en URL d'intégration « nocookie ».
     */
    public function embedUrl(): ?string
    {
        $url = trim((string) $this->video_url);

        if ($url === '' || ! in_array($this->video_provider, ['link', 'bunny'], true)) {
            return null;
        }

        $library = config('services.bunny.library_id');
        $guid = null;
        if (preg_match('~iframe\.mediadelivery\.net/(?:embed|play)/\d+/([0-9a-f-]{36})~i', $url, $m)) {
            $guid = $m[1];
        } elseif (preg_match('~^[0-9a-f-]{36}$~i', $url)) {
            $guid = $url;
        }

        if ($guid && $library) {
            return $this->bunnyEmbed($library, $guid);
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{11})~', $url, $m)) {
            return "https://www.youtube-nocookie.com/embed/{$m[1]}?rel=0";
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        return null;
    }

    private function bunnyEmbed(string $library, string $guid): string
    {
        $base = "https://iframe.mediadelivery.net/embed/{$library}/{$guid}";
        $tokenKey = config('services.bunny.token_key');

        if (blank($tokenKey)) {
            return $base;
        }

        // Jeton d'intégration Bunny : SHA256(tokenKey + guid + expiration).
        $expires = now()->addSeconds((int) config('services.bunny.token_ttl', 14400))->timestamp;
        $token = hash('sha256', $tokenKey.$guid.$expires);

        return "{$base}?token={$token}&expires={$expires}";
    }
}
