<?php

namespace Tests\Unit;

use App\Support\ContactDetector;
use PHPUnit\Framework\TestCase;

class ContactDetectionTest extends TestCase
{
    public function test_detects_a_local_phone_number_with_spaces(): void
    {
        $matches = ContactDetector::scan('Appelez-moi au 07 00 00 00 00 dès que possible.');

        $this->assertArrayHasKey('telephone', $matches);
        $this->assertContains('07 00 00 00 00', $matches['telephone']);
    }

    public function test_detects_phone_numbers_in_various_formats(): void
    {
        $formats = [
            '0700000000',
            '07.00.00.00.00',
            '07-00-00-00-00',
            '+225 07 00 00 00 00',
            '00225 07 00 00 00 00',
        ];

        foreach ($formats as $format) {
            $matches = ContactDetector::scan("Mon numéro : {$format}");
            $this->assertArrayHasKey('telephone', $matches, "Format non détecté : {$format}");
        }
    }

    public function test_does_not_flag_a_short_number_that_is_not_a_phone_number(): void
    {
        $matches = ContactDetector::scan('Je voudrais 10 kg de manioc, livrés le 12-05.');

        $this->assertArrayNotHasKey('telephone', $matches);
    }

    public function test_detects_an_email_address(): void
    {
        $matches = ContactDetector::scan('Écrivez-moi à jean.kouassi@example.com pour plus de détails.');

        $this->assertArrayHasKey('email', $matches);
        $this->assertContains('jean.kouassi@example.com', $matches['email']);
    }

    public function test_detects_a_whatsapp_link(): void
    {
        $matches = ContactDetector::scan('Rejoignez-moi ici : https://wa.me/2250700000000');

        $this->assertArrayHasKey('lien_whatsapp', $matches);
    }

    public function test_detects_a_facebook_link(): void
    {
        $matches = ContactDetector::scan('Retrouvez-moi sur facebook.com/fermekouassi');

        $this->assertArrayHasKey('lien_reseau_social', $matches);
    }

    public function test_detects_an_instagram_link(): void
    {
        $matches = ContactDetector::scan('instagram.com/ferme_kouassi c\'est moi');

        $this->assertArrayHasKey('lien_reseau_social', $matches);
    }

    public function test_detects_common_contact_phrases_without_an_explicit_number(): void
    {
        $matches = ContactDetector::scan('Contactez-moi directement, ce sera plus simple.');

        $this->assertArrayHasKey('formule_contact', $matches);
    }

    public function test_a_plain_message_with_no_contact_attempt_is_not_flagged(): void
    {
        $this->assertFalse(ContactDetector::isFlagged('Bonjour, je suis intéressé par votre offre de manioc frais.'));
        $this->assertSame([], ContactDetector::scan('Quelle est la quantité disponible cette semaine ?'));
    }

    public function test_masks_every_detected_extract_and_leaves_the_rest_untouched(): void
    {
        $text = 'Appelez-moi au 07 00 00 00 00 ou par mail jean@example.com.';
        $matches = ContactDetector::scan($text);

        $masked = ContactDetector::mask($text, $matches);

        $this->assertStringNotContainsString('07 00 00 00 00', $masked);
        $this->assertStringNotContainsString('jean@example.com', $masked);
        $this->assertStringContainsString('[coordonnée masquée]', $masked);
        $this->assertStringContainsString('Appelez-moi au', $masked);
        $this->assertStringContainsString('ou par mail', $masked);
    }

    public function test_masking_an_unflagged_message_returns_it_unchanged(): void
    {
        $text = 'Bonjour, tout est clair pour moi, merci.';

        $this->assertSame($text, ContactDetector::mask($text, ContactDetector::scan($text)));
    }

    public function test_detects_multiple_distinct_signals_in_the_same_message(): void
    {
        $matches = ContactDetector::scan('Contactez-moi au 07 00 00 00 00 ou sur wa.me/2250700000000, mon mail jean@example.com.');

        $this->assertArrayHasKey('telephone', $matches);
        $this->assertArrayHasKey('email', $matches);
        $this->assertArrayHasKey('lien_whatsapp', $matches);
        $this->assertArrayHasKey('formule_contact', $matches);
    }
}
