<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Sauvegarde autonome (sans dépendance) : dump MySQL + archive des fichiers privés
 * (preuves de paiement), dans storage/app/backups/. Conserve les N plus récentes.
 *
 * Programmée quotidiennement (voir routes/console.php). Sur LWS : cron `schedule:run`.
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--keep=14 : Nombre de sauvegardes à conserver}';

    protected $description = 'Sauvegarde la base de données et les fichiers privés';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Y-m-d_His');
        $zipPath = "{$dir}/backup_{$stamp}.zip";
        $sqlPath = "{$dir}/db_{$stamp}.sql";

        if (! $this->dumpDatabase($sqlPath)) {
            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Impossible de créer l'archive {$zipPath}.");
            @unlink($sqlPath);

            return self::FAILURE;
        }

        $zip->addFile($sqlPath, 'database.sql');
        $this->addDirectory($zip, storage_path('app/private'), 'private');
        $zip->close();

        @unlink($sqlPath);

        $this->prune($dir, (int) $this->option('keep'));

        $this->info('Sauvegarde créée : '.basename($zipPath).' ('.$this->humanSize(filesize($zipPath)).')');

        return self::SUCCESS;
    }

    private function dumpDatabase(string $path): bool
    {
        $c = config('database.connections.'.config('database.default'));

        if (($c['driver'] ?? null) !== 'mysql') {
            $this->warn('Sauvegarde SQL ignorée : le driver n\'est pas mysql.');
            file_put_contents($path, "-- driver non mysql, dump ignoré\n");

            return true;
        }

        $binary = env('DB_DUMP_BINARY', 'mysqldump');

        $process = new Process([
            $binary,
            '--host='.$c['host'],
            '--port='.$c['port'],
            '--user='.$c['username'],
            '--password='.$c['password'],
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--skip-lock-tables',
            $c['database'],
        ]);
        $process->setTimeout(600);

        $out = fopen($path, 'w');
        $process->run(function ($type, $buffer) use ($out) {
            if ($type === Process::OUT) {
                fwrite($out, $buffer);
            }
        });
        fclose($out);

        if (! $process->isSuccessful()) {
            $this->error('mysqldump a échoué : '.trim($process->getErrorOutput() ?: 'binaire introuvable ('.$binary.')'));
            @unlink($path);

            return false;
        }

        return true;
    }

    private function addDirectory(ZipArchive $zip, string $path, string $localBase): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (File::allFiles($path) as $file) {
            $zip->addFile($file->getPathname(), $localBase.'/'.$file->getRelativePathname());
        }
    }

    private function prune(string $dir, int $keep): void
    {
        $backups = collect(File::glob("{$dir}/backup_*.zip"))
            ->sortByDesc(fn ($p) => filemtime($p))
            ->values();

        $backups->slice($keep)->each(fn ($p) => @unlink($p));
    }

    private function humanSize(int $bytes): string
    {
        foreach (['o', 'Ko', 'Mo', 'Go'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' To';
    }
}
