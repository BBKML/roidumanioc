<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\FormationAccess;
use App\Enums\FormationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Formation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'category', 'description',
        'price', 'access', 'status', 'image_path', 'duration_label', 'position',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'position' => 'integer',
            'access' => FormationAccess::class,
            'status' => FormationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Formation $formation) {
            if (blank($formation->slug)) {
                $formation->slug = Str::slug($formation->title).'-'.Str::random(5);
            }
            $formation->access = $formation->price > 0 ? FormationAccess::Premium : FormationAccess::Gratuit;
        });
    }

    /* ---------------- Relations ---------------- */

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /* ---------------- Scopes ---------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FormationStatus::Publiee);
    }

    /* ---------------- Helpers ---------------- */

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('status', EnrollmentStatus::Validee)->count();
    }

    /**
     * Avancement d'un apprenant : ['done' => int, 'total' => int, 'pct' => int].
     */
    public function progressFor(User $user): array
    {
        $lessonIds = $this->lessons()->pluck('id');
        $total = $lessonIds->count();
        $done = $total
            ? LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $lessonIds)->count()
            : 0;

        return [
            'done' => $done,
            'total' => $total,
            'pct' => $total ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
