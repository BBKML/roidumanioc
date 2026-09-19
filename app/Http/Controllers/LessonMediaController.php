<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les vidéos téléversées et les ressources d'une leçon.
 * Accès réservé aux apprenants ayant le droit de suivre la formation (FormationPolicy@follow),
 * l'admin passe via Gate::before. Rien n'est exposé en URL directe.
 */
class LessonMediaController extends Controller
{
    public function video(Request $request, Lesson $lesson): Response
    {
        $this->authorize('follow', $lesson->formation);

        abort_unless(
            $lesson->video_provider === 'upload' && filled($lesson->video_path),
            404,
        );

        return $this->serve($lesson->video_disk ?: 'local', $lesson->video_path, inline: true);
    }

    public function attachment(Request $request, LessonAttachment $attachment): Response
    {
        $this->authorize('follow', $attachment->lesson->formation);

        // PDF / images : affichage en ligne ; autres : téléchargement.
        return $this->serve(
            $attachment->disk,
            $attachment->path,
            inline: $attachment->isPdf() || str_starts_with((string) $attachment->mime, 'image/'),
            downloadName: $attachment->title,
        );
    }

    private function serve(string $disk, string $path, bool $inline, ?string $downloadName = null): Response
    {
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($path), 404);

        $disposition = $inline ? 'inline' : 'attachment';
        $name = $downloadName ? $this->safeName($downloadName, $path) : basename($path);

        // Disque local : réponse fichier native -> supporte les requêtes Range (streaming/seek).
        if (config("filesystems.disks.$disk.driver") === 'local') {
            return response()->file($storage->path($path), [
                'Content-Disposition' => "$disposition; filename=\"$name\"",
            ]);
        }

        return $storage->response($path, $name, [], $disposition);
    }

    private function safeName(string $title, string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $base = Str::slug($title) ?: 'fichier';

        return $ext ? "$base.$ext" : $base;
    }
}
