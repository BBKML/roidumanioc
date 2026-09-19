<?php

namespace Tests\Unit;

use App\Models\Award;
use App\Models\Testimonial;
use Tests\TestCase;

/**
 * `Testimonial`/`Award::localized()` partagent désormais App\Models\Concerns\
 * HasLocalizedFallback (audit de logique — deux implémentations identiques dupliquées
 * risquaient de diverger silencieusement). Un seul test pour les deux modèles, qui
 * exerce le trait à travers chacun d'eux. Étend Tests\TestCase (pas le TestCase PHPUnit
 * nu utilisé par ContactDetectionTest/PhoneNumberTest) : app()->setLocale()/config() et
 * l'instanciation d'un Eloquent Model exigent le conteneur Laravel démarré — ces deux
 * autres tests n'exercent qu'une classe statique pure, sans cette dépendance.
 */
class HasLocalizedFallbackTest extends TestCase
{
    public function test_testimonial_returns_the_french_value_when_locale_is_french(): void
    {
        app()->setLocale('fr');

        $testimonial = new Testimonial(['quote' => 'Texte FR', 'quote_en' => 'Text EN']);

        $this->assertSame('Texte FR', $testimonial->localized('quote'));
    }

    public function test_testimonial_returns_the_english_value_when_locale_is_english(): void
    {
        app()->setLocale('en');

        $testimonial = new Testimonial(['quote' => 'Texte FR', 'quote_en' => 'Text EN']);

        $this->assertSame('Text EN', $testimonial->localized('quote'));
    }

    public function test_testimonial_falls_back_to_french_when_the_english_translation_is_empty(): void
    {
        app()->setLocale('en');

        $testimonial = new Testimonial(['quote' => 'Texte FR', 'quote_en' => null]);

        $this->assertSame('Texte FR', $testimonial->localized('quote'));
    }

    public function test_award_returns_the_french_value_when_locale_is_french(): void
    {
        app()->setLocale('fr');

        $award = new Award(['title' => 'Titre FR', 'title_en' => 'Title EN']);

        $this->assertSame('Titre FR', $award->localized('title'));
    }

    public function test_award_falls_back_to_french_when_the_english_translation_is_empty(): void
    {
        app()->setLocale('en');

        $award = new Award(['title' => 'Titre FR', 'title_en' => '']);

        $this->assertSame('Titre FR', $award->localized('title'));
    }

    protected function tearDown(): void
    {
        app()->setLocale(config('locales.default', 'fr'));

        parent::tearDown();
    }
}
