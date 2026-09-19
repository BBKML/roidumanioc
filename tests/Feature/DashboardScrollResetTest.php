<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Correction UX : après un clic sur un lien de menu du back-office ou de l'espace
 * apprenant (wire:navigate), la nouvelle page devait s'afficher là où l'ancienne page
 * était scrollée, obligeant l'utilisateur à remonter manuellement.
 *
 * Cause : le mécanisme de remise à zéro du scroll intégré à wire:navigate agit sur
 * `document.body` (`body.scrollTo()`), mais `html,body{height:100%}` (admin.css, partagé
 * par le layout admin ET apprenant) fait que c'est <html> qui défile réellement sur ces
 * pages — l'appel de Livewire ne déplace donc rien à l'écran.
 *
 * Il n'existe pas d'outil de test navigateur (Dusk, etc.) dans ce projet et il n'a pas été
 * ajouté (aucune nouvelle dépendance) : ce test ne peut donc pas exécuter le JavaScript ni
 * vérifier la position de défilement réelle. Il garde uniquement une trace du correctif
 * dans le code source, pour repérer une régression si ce câblage venait à être retiré par
 * erreur — la vérification visuelle reste à faire manuellement (desktop et mobile).
 */
class DashboardScrollResetTest extends TestCase
{
    public function test_admin_js_resets_the_scroll_position_after_each_wire_navigate(): void
    {
        $js = File::get(resource_path('js/admin.js'));

        $this->assertStringContainsString(
            "document.addEventListener('livewire:navigated', resetScrollAfterNavigate)",
            $js,
            'admin.js doit remettre le scroll en haut de page après chaque navigation wire:navigate (utilisée par le menu admin ET apprenant, qui partagent ce fichier).'
        );

        $this->assertStringContainsString('window.scrollTo(', $js);
    }
}
