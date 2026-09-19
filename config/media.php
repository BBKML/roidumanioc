<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vidéos de leçon téléversées
    |--------------------------------------------------------------------------
    |
    | Disque privé par défaut : les fichiers ne sont jamais exposés directement,
    | ils passent par la route `lessons.video` qui vérifie l'inscription.
    |
    | ⚠️ Hébergement mutualisé (LWS) : `upload_max_filesize` / `post_max_size` du
    | serveur priment. Pour de gros fichiers, préférez Bunny Stream (source « bunny »)
    | ou pointez MEDIA_VIDEO_DISK vers un stockage objet (s3).
    |
    */
    'video_disk' => env('MEDIA_VIDEO_DISK', 'local'),
    'video_max_mb' => (int) env('MEDIA_VIDEO_MAX_MB', 200),
    'video_mimetypes' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'],

    /*
    |--------------------------------------------------------------------------
    | Ressources jointes (PDF, fiches…)
    |--------------------------------------------------------------------------
    */
    'attachment_disk' => env('MEDIA_ATTACHMENT_DISK', 'local'),
    'attachment_max_mb' => (int) env('MEDIA_ATTACHMENT_MAX_MB', 25),
    'attachment_mimetypes' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],

];
