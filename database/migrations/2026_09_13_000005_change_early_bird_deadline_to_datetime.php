<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `timestamp` fait convertir MySQL la valeur via le fuseau *session* du serveur (`SYSTEM`,
 * pas forcément UTC — vérifié en dev : SYSTEM = UTC+2) à l'écriture et à la lecture, alors que
 * l'app (config('app.timezone') = UTC) traite systématiquement la valeur comme déjà en UTC.
 * Sur un seul serveur stable ça ne se voit pas (même décalage appliqué à l'écriture et à la
 * lecture), mais un changement de fuseau serveur (bascule DST, migration d'hébergement, prod
 * différente du poste de dev) ferait dériver silencieusement l'échéance affichée. `dateTime`
 * stocke la valeur telle quelle, sans aucune conversion — cohérent avec le reste de l'app qui
 * raisonne uniquement en UTC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->dateTime('early_bird_deadline')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->timestamp('early_bird_deadline')->nullable()->change();
        });
    }
};
