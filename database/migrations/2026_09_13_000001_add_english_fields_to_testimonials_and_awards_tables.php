<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->text('quote_en')->nullable()->after('quote');
            $table->string('author_role_en')->nullable()->after('author_role');
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['quote_en', 'author_role_en']);
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });
    }
};
