<?php

use App\Http\Controllers\Admin\NewsletterExportController;
use App\Http\Controllers\Admin\RegistrationLeadExportController;
use App\Http\Controllers\Admin\RegistrationLeadTemplateController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FormationsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonMediaController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Account\Settings as AccountSettings;
use App\Livewire\Admin\ActivityLog as AdminActivityLog;
use App\Livewire\Admin\BuyerNeeds as AdminBuyerNeeds;
use App\Livewire\Admin\Buyers as AdminBuyers;
use App\Livewire\Admin\Collaborations as AdminCollaborations;
use App\Livewire\Admin\CommunityModeration;
use App\Livewire\Admin\ConnectionRequests as AdminConnectionRequests;
use App\Livewire\Admin\ContentManager;
use App\Livewire\Admin\Conversations as AdminConversations;
use App\Livewire\Admin\CropOffers as AdminCropOffers;
use App\Livewire\Admin\CropOrders as AdminCropOrders;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\DeliveryAssists as AdminDeliveryAssists;
use App\Livewire\Admin\Events as AdminEvents;
use App\Livewire\Admin\Formations as AdminFormations;
use App\Livewire\Admin\LessonManager;
use App\Livewire\Admin\MarketplaceModeration;
use App\Livewire\Admin\Members as AdminMembers;
use App\Livewire\Admin\Messages as AdminMessages;
use App\Livewire\Admin\Newsletter as AdminNewsletter;
use App\Livewire\Admin\Orders as AdminOrders;
use App\Livewire\Admin\Payments as AdminPayments;
use App\Livewire\Admin\PaymentSettings;
use App\Livewire\Admin\Producers as AdminProducers;
use App\Livewire\Admin\ProducerShow as AdminProducerShow;
use App\Livewire\Admin\RegistrationFormBuilder;
use App\Livewire\Admin\RegistrationForms as AdminRegistrationForms;
use App\Livewire\Admin\RegistrationLeads as AdminRegistrationLeads;
use App\Livewire\Admin\Reviews as AdminReviews;
use App\Livewire\Admin\ShopProducts;
use App\Livewire\Buyer\CropOrderForm as BuyerCropOrderForm;
use App\Livewire\Buyer\Favorites as BuyerFavorites;
use App\Livewire\Buyer\NeedForm as BuyerNeedForm;
use App\Livewire\Buyer\Needs as BuyerNeeds;
use App\Livewire\Buyer\Requests as BuyerRequests;
use App\Livewire\Collaboration\Show as CollaborationShow;
use App\Livewire\Connect\RequestNeed as ConnectRequestNeed;
use App\Livewire\Connect\RequestOffer as ConnectRequestOffer;
use App\Livewire\Connect\Show as ConnectShow;
use App\Livewire\CropOrder\Show as CropOrderShow;
use App\Livewire\Learner\BuyerProfile as LearnerBuyerProfile;
use App\Livewire\Learner\Catalog;
use App\Livewire\Learner\Checkout;
use App\Livewire\Learner\Community as LearnerCommunity;
use App\Livewire\Learner\CourseViewer;
use App\Livewire\Learner\Dashboard as LearnerDashboard;
use App\Livewire\Learner\ListingCheckout;
use App\Livewire\Learner\Marketplace as LearnerMarketplace;
use App\Livewire\Learner\Orders as LearnerOrders;
use App\Livewire\Learner\ProducerProfile as LearnerProducerProfile;
use App\Livewire\Learner\Progress as LearnerProgress;
use App\Livewire\Learner\ShopCheckout;
use App\Livewire\Producer\OfferForm as ProducerOfferForm;
use App\Livewire\Producer\Offers as ProducerOffers;
use App\Livewire\Producer\Requests as ProducerRequests;
use App\Livewire\Public\Needs as PublicNeeds;
use App\Livewire\Public\Producers as PublicProducers;
use App\Livewire\Public\ProducerShow as PublicProducerShow;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public (vitrine)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/langue/{locale}', function (string $locale) {
    abort_unless(in_array($locale, config('locales.supported'), true), 404);

    Cookie::queue(Cookie::forever('locale', $locale));

    return back();
})->name('locale.switch');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:6,1')
    ->name('contact.send');

// Catalogue et marketplace consultables publiquement (l'inscription/connexion n'est requise
// qu'au moment de s'inscrire à une formation ou de commander) + pages dédiées ex-ancres.
Route::get('/formations', [FormationsController::class, 'index'])->name('formations.index');
Route::get('/formations/{formation:slug}', [FormationsController::class, 'show'])->name('formations.show');
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
// Catalogues producteurs / besoins (Phase 4) : distincts de /marketplace (MarketplaceListing
// reste le panneau d'annonces existant), structurés et filtrables, alimentés par
// producer_profiles/crop_offers et buyer_needs (Phases 1-3).
Route::get('/producteurs', PublicProducers::class)->name('producers.index');
// Fiche publique d'un producteur : toutes ses offres publiées (pas de plafond à 3 comme sur
// la carte du catalogue), aucune coordonnée personnelle (§8.2, même garde que le catalogue).
Route::get('/producteurs/{producerProfile}', PublicProducerShow::class)->name('producers.show');
Route::get('/besoins', PublicNeeds::class)->name('needs.index');
// Favoris (§11) : ouvert à tout utilisateur connecté (pas seulement un buyer_profiles déjà
// créé) — contrôleur classique plutôt qu'une action Livewire pour profiter gratuitement du
// guest→connexion→retour standard du middleware `auth` (cf. FavoriteController).
Route::middleware(['auth', 'active'])->post('/producteurs/{producerProfile}/favori', FavoriteController::class)
    ->name('producers.favorite.toggle');
Route::get('/placali', [PageController::class, 'placali'])->name('placali.show');
Route::get('/communaute', [PageController::class, 'communaute'])->name('communaute.show');
Route::get('/fondateur', [PageController::class, 'fondateur'])->name('fondateur.show');
Route::get('/evenements', [PageController::class, 'evenements'])->name('events.index');
// Mentions légales / confidentialité (§44) — texte fourni par Le Roi du Manioc, saisi
// dans le CMS (Admin\ContentManager, section « legal »), jamais rédigé côté code.
Route::get('/mentions-legales', [PageController::class, 'legalNotice'])->name('legal.notice');
Route::get('/politique-de-confidentialite', [PageController::class, 'privacyPolicy'])->name('legal.privacy');
// Règles de publication/comportement, gestion des litiges, politique de paiement (§44) —
// regroupées dans UN document plutôt que 4 pages distinctes (choix éditorial : ces 4 sujets
// se lisent naturellement comme les chapitres d'une même charte, et 6 liens de pied de page
// auraient nui à la lisibilité du footer pour un gain nul).
Route::get('/charte-d-utilisation', [PageController::class, 'usageCharter'])->name('legal.charter');

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('newsletter.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Pages d'inscription publiques (campagnes réseaux sociaux) — lien partagé, contenu géré
// depuis l'admin (Admin\RegistrationForms).
Route::get('/inscription/{form:slug}', [RegistrationFormController::class, 'show'])->name('inscription.show');
Route::get('/inscription/{form:slug}/affiche', [RegistrationFormController::class, 'image'])->name('inscription.image');
Route::post('/inscription/{form:slug}', [RegistrationFormController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('inscription.store');

/*
|--------------------------------------------------------------------------
| Redirection après connexion — selon le rôle
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Espace apprenant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->prefix('mon-espace')->name('learner.')->group(function () {
    Route::get('/', LearnerDashboard::class)->name('dashboard');
    Route::get('/catalogue', Catalog::class)->name('catalog');
    Route::get('/formations/{formation}', CourseViewer::class)->name('course');
    Route::get('/formations/{formation}/paiement', Checkout::class)->name('checkout');
    Route::get('/boutique/{product}/paiement', ShopCheckout::class)->name('shop-checkout');
    Route::get('/marketplace/{listing}/commander', ListingCheckout::class)->name('listing-checkout');
    Route::get('/progression', LearnerProgress::class)->name('progress');
    Route::get('/marketplace', LearnerMarketplace::class)->name('marketplace');
    Route::get('/commandes', LearnerOrders::class)->name('orders');
    Route::get('/communaute', LearnerCommunity::class)->name('community');
    // Activation / édition des espaces producteur & acheteur (page d'onboarding elle-même —
    // pas sous 'producer'/'buyer', réservés aux pages qui exigent un profil déjà créé).
    Route::get('/producteur', LearnerProducerProfile::class)->name('producer');
    Route::get('/acheteur', LearnerBuyerProfile::class)->name('buyer');

    // Offres du producteur : exigent un producer_profiles déjà créé (middleware 'producer',
    // persistant Livewire — re-vérifié à chaque wire:click, cf. EnsureUserIsProducer).
    Route::middleware('producer')->prefix('producteur')->name('producer.')->group(function () {
        Route::get('/offres', ProducerOffers::class)->name('offers');
        Route::get('/offres/creer', ProducerOfferForm::class)
            ->middleware('throttle:6,1')->name('offers.create');
        Route::get('/offres/{offer}/modifier', ProducerOfferForm::class)->name('offers.edit');
        // Le producteur répond à un besoin acheteur (amorce une mise en relation, Phase 5).
        Route::get('/besoins/{need}/repondre', ConnectRequestNeed::class)
            ->middleware('throttle:10,1')->name('needs.respond');
        Route::get('/demandes', ProducerRequests::class)->name('requests');
    });

    // Besoins de l'acheteur : exigent un buyer_profiles déjà créé (middleware 'buyer',
    // persistant Livewire — re-vérifié à chaque wire:click, cf. EnsureUserIsBuyer).
    Route::middleware('buyer')->prefix('acheteur')->name('buyer.')->group(function () {
        Route::get('/favoris', BuyerFavorites::class)->name('favorites');
        Route::get('/besoins', BuyerNeeds::class)->name('needs');
        Route::get('/besoins/creer', BuyerNeedForm::class)
            ->middleware('throttle:6,1')->name('needs.create');
        Route::get('/besoins/{need}/modifier', BuyerNeedForm::class)->name('needs.edit');
        // L'acheteur contacte une offre producteur (amorce une mise en relation, Phase 5).
        Route::get('/offres/{offer}/contacter', ConnectRequestOffer::class)
            ->middleware('throttle:10,1')->name('offers.contact');
        Route::get('/demandes', BuyerRequests::class)->name('requests');
        // Parcours de commande structuré (§1/§2) — distinct de la mise en relation par chat
        // ci-dessus : formulaire de commande, jamais acceptée automatiquement.
        Route::get('/offres/{offer}/commander', BuyerCropOrderForm::class)
            ->middleware('throttle:10,1')->name('crop-orders.create');
    });

    // Détail d'une demande de mise en relation — partagé, gardé par ConnectionRequestPolicy
    // (les deux parties uniquement), pas par 'producer'/'buyer' (un admin doit aussi pouvoir
    // l'ouvrir depuis /admin/journal sans avoir de profil producteur ou acheteur).
    Route::get('/demandes/{connectionRequest}', ConnectShow::class)->name('requests.show');

    // « Ma collaboration » — ouverte dès collaboration_confirmee (App\Livewire\Connect\Show).
    // Même logique que ci-dessus : partagée, gardée par CollaborationPolicy uniquement.
    Route::get('/collaborations/{collaboration}', CollaborationShow::class)->name('collaborations.show');

    // Détail d'une commande structurée — partagé, gardé par CropOrderPolicy uniquement
    // (pas par 'producer'/'buyer', même raisonnement que ci-dessus : un admin doit pouvoir
    // l'ouvrir en lecture seule depuis /admin/commandes-produits).
    Route::get('/commandes-produits/{cropOrder}', CropOrderShow::class)->name('crop-orders.show');
});

// Capture de paiement — accessible à l'admin et au propriétaire (contrôle dans le contrôleur).
// 'active' : un compte suspendu ne doit pas garder l'accès à une preuve de paiement via un
// lien en session déjà ouverte, même sans repasser par une route gardée par ce middleware.
Route::middleware(['auth', 'active'])->get('/paiements/{payment}/preuve', PaymentProofController::class)->name('payments.proof');

// Médias de leçon — vidéo téléversée & ressources, gardés par l'inscription (FormationPolicy@follow).
// 'active' : même raisonnement que payments.proof — sans lui, un compte suspendu en session
// active garderait le streaming/téléchargement tant qu'il ne visite aucune route 'active'.
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/lecons/{lesson}/video', [LessonMediaController::class, 'video'])->name('lessons.video');
    Route::get('/ressources/{attachment}', [LessonMediaController::class, 'attachment'])->name('lessons.attachment');
});

/*
|--------------------------------------------------------------------------
| Connexion Google (OAuth2)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
        ->middleware('throttle:10,1')->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->middleware('throttle:10,1')->name('google.callback');
});

/*
|--------------------------------------------------------------------------
| Mon compte (apprenant & admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->get('/mon-compte', AccountSettings::class)->name('account.edit');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');

    // Phase 5 — vérification des paiements & commandes
    Route::get('/paiements', AdminPayments::class)->name('payments');
    Route::get('/commandes', AdminOrders::class)->name('orders');

    // Phase 3 — contenu & catalogue
    Route::get('/contenu', ContentManager::class)->name('content');
    Route::get('/formations', AdminFormations::class)->name('formations');
    Route::get('/formations/{formation}/lecons', LessonManager::class)->name('lessons');
    Route::get('/marketplace', MarketplaceModeration::class)->name('marketplace');
    Route::get('/boutique', ShopProducts::class)->name('shop');
    Route::get('/evenements', AdminEvents::class)->name('events');
    Route::get('/communaute', CommunityModeration::class)->name('community');
    Route::get('/parametres', PaymentSettings::class)->name('settings');

    // Phase 6 — comptes & rôles
    Route::get('/membres', AdminMembers::class)->name('members');

    // Phase 7 — audit
    Route::get('/journal', AdminActivityLog::class)->name('activity');

    // Mise en relation — modération des conversations signalées (§16, lecture seule).
    Route::get('/conversations', AdminConversations::class)->name('conversations');

    // Phase 10 — supervision de la mise en relation (§25/§37). /producteurs remplace
    // l'ancienne route producer-verification (Phase 8) : même écran étendu (recherche +
    // fiche détail), pas de doublon. Les chiffres d'ensemble (ex-/mise-en-relation) ont
    // rejoint le tableau de bord unique (Phase 16) — plus de route dédiée ici.
    Route::get('/producteurs', AdminProducers::class)->name('producers');
    Route::get('/producteurs/{producerProfile}', AdminProducerShow::class)->name('producers.show');
    Route::get('/acheteurs', AdminBuyers::class)->name('buyers');
    Route::get('/offres', AdminCropOffers::class)->name('crop-offers');
    Route::get('/besoins', AdminBuyerNeeds::class)->name('buyer-needs');
    Route::get('/demandes', AdminConnectionRequests::class)->name('connection-requests');
    Route::get('/collaborations', AdminCollaborations::class)->name('collaborations');
    // Modération des avis (Phase 12, §25) — masquage seul, jamais d'édition/suppression du
    // contenu déposé (cf. Review, immuable).
    Route::get('/avis', AdminReviews::class)->name('reviews');

    // Parcours de commande structuré (§10) — distinct de la mise en relation ci-dessus.
    // /commandes-produits : supervision en lecture seule. /aide-livraison : seul pouvoir
    // propre de l'admin sur App\Models\CropOrder (markDeliveryAssistStep).
    Route::get('/commandes-produits', AdminCropOrders::class)->name('crop-orders');
    Route::get('/aide-livraison', AdminDeliveryAssists::class)->name('delivery-assists');

    // Site dynamique — messages & infolettre
    Route::get('/messages', AdminMessages::class)->name('messages');
    Route::get('/infolettre', AdminNewsletter::class)->name('newsletter');
    Route::get('/infolettre/export', NewsletterExportController::class)->name('newsletter.export');

    // Campagnes — formulaires d'inscription publics & prospects captés
    Route::get('/inscriptions/formulaires', AdminRegistrationForms::class)->name('registration-forms');
    Route::get('/inscriptions/formulaires/nouveau', RegistrationFormBuilder::class)->name('registration-forms.create');
    Route::get('/inscriptions/formulaires/{form:slug}/edition', RegistrationFormBuilder::class)->name('registration-forms.edit');
    Route::get('/inscriptions/prospects', AdminRegistrationLeads::class)->name('registration-leads');
    Route::get('/inscriptions/prospects/export', RegistrationLeadExportController::class)->name('registration-leads.export');
    Route::get('/inscriptions/prospects/modele', RegistrationLeadTemplateController::class)->name('registration-leads.template');
});

require __DIR__.'/auth.php';
