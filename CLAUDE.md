# Le Roi du Manioc — repères pour l'IA / les devs

Application Laravel 13 (Blade + Livewire 4, MySQL). Reconstruit la maquette
`../index.html` (vitrine) et `../tableau-de-bord.html` (admin + apprenant).
Voir `README.md` pour l'installation et le mapping des tables.

**4 « mondes » CSS/JS séparés (entrées Vite)** — pas de Tailwind côté app :
`vitrine.{css,js}` (site public), `admin.{css,js}` (back-office **et** espace apprenant),
`auth.{css,js}` (connexion/inscription, layout `layouts/guest.blade.php` à 2 colonnes repris
de la maquette). `app.{css,js}` (Tailwind Breeze) est résiduel et n'est plus chargé nulle part.
Le monde vitrine reste 100 % `vitrine.{css,js}` — mais depuis la Phase 4, `components.public-
layout` charge aussi `@livewireStyles`/`@livewireScripts` (+ meta `csrf-token`) car deux pages
publiques (`/producteurs`, `/besoins`) sont des composants Livewire (recherche/filtres avec
pagination, cf. plus bas) ; toutes les autres pages publiques restent des contrôleurs Blade
classiques, ce n'est pas un changement d'architecture générale.

## Conventions

- **Statuts = enums PHP** dans `app/Enums/`, colonnes `string` en base.
- **Rôles** : `admin`, `apprenant` (`users.role`). Un admin actif passe toutes les Gates
  (`AppServiceProvider::boot`). Middleware `admin` sur `/admin`, `active` (déconnecte les
  comptes suspendus) sur `/mon-espace` et `/mon-compte`. `EnsureUserIsAdmin`/`EnsureUserIsActive`
  sont **persistants Livewire** (`Livewire::addPersistentMiddleware`) → re-vérifiés à chaque
  `wire:click`, pas seulement au GET initial.
- **Comptes** : `role`/`status` ne viennent JAMAIS d'une requête utilisateur (valeurs par défaut
  BDD ou `forceFill` explicite). `App\Livewire\Account\Settings` (`/mon-compte`, layout choisi
  selon le rôle). `App\Livewire\Admin\Members` : gardes anti-verrouillage
  (`User::isLastActiveAdmin()`, pas d'action sur soi-même, suppression bloquée si historique de
  paiements → suspendre). Reset mdp admin = mot de passe temporaire montré une fois + activitylog.
  - **Connexion par e-mail OU téléphone** : beaucoup de clients n'ont pas d'e-mail mais ont tous
    un téléphone. `users.email` est `nullable` (unique si renseigné), `users.phone` est
    `nullable`+**unique**, toujours stocké normalisé — chiffres uniquement, indicatif `225`/`00225`
    retiré (`App\Support\PhoneNumber::normalize()`, appliqué via un mutateur `Attribute` sur
    `User::phone()` **et** explicitement avant chaque validation qui vérifie l'unicité, car
    `unique:` compare la valeur brute soumise, pas la valeur mutée). Formulaire d'inscription
    (`RegisteredUserController`) et création de membre (`Admin\Members`) : `email`/`phone` sont
    tous deux `nullable` + `required_without` l'un de l'autre (au moins un des deux obligatoire) —
    même règle dans `Account\Settings::updateProfile`. Connexion (`LoginRequest`, un seul champ
    `login`) : détecte e-mail vs téléphone via la présence d'un `@`, normalise si téléphone, puis
    `Auth::attempt(['email'|'phone' => ..., 'password' => ...])` (colonne `phone` interrogée
    nativement, pas de `UserProvider` custom nécessaire). Un compte créé sans e-mail ne peut pas
    utiliser "mot de passe oublié" (toujours par e-mail) — non traité en v1, contacter l'admin.
    Google Sign-In reste exclusivement par e-mail (inchangé, cf. ci-dessous). Ne jamais réintroduire
    un stockage de téléphone "au format libre" (espaces, indicatif variable) sans repasser par
    `PhoneNumber::normalize()`, sous peine de casser la connexion et l'unicité.
  - **Notifications e-mail liées aux comptes** : une inscription publique (`RegisteredUserController`)
    notifie l'admin actif par e-mail (`NewMemberMail`, `try/catch` comme les autres notifications —
    ne bloque jamais l'inscription). Une création manuelle par l'admin (`Admin\Members::createMember`)
    envoie l'identifiant + mot de passe temporaire au membre par e-mail s'il a une adresse
    (`MemberWelcomeMail`) ; même chose pour une réinitialisation de mot de passe par l'admin
    (`MemberPasswordResetMail`). Rien n'est envoyé si le membre n'a qu'un téléphone (v1, pas de SMS) —
    le mot de passe reste alors affiché une seule fois à l'admin comme avant.
- **Google Sign-In** : `App\Services\GoogleOAuth` (OAuth2 « authorization code » sans dépendance,
  via `Http::`), `Auth\GoogleController`. `state` anti-CSRF, `email_verified` obligatoire,
  liaison par e-mail vérifié, compte créé = apprenant actif (jamais admin), compte suspendu
  refusé. Désactivé si `GOOGLE_CLIENT_ID` vide (bouton masqué + routes 404).
- **Google Sign-In** : `App\Services\GoogleOAuth` (OAuth2 « authorization code » sans dépendance,
  via `Http::`), `Auth\GoogleController`. `state` anti-CSRF, `email_verified` obligatoire,
  liaison par e-mail vérifié, compte créé = apprenant actif (jamais admin), compte suspendu
  refusé. Désactivé si `GOOGLE_CLIENT_ID` vide (bouton masqué + routes 404).
- **Vitrine 100 % base de données** : `SiteContent` (CMS, 1 ligne/section), `Testimonial`,
  `Award`, + les modèles catalogue. Rien en dur dans les vues. Layout unique
  `resources/views/components/public-layout.blade.php` (en-tête/pied + SEO/OG dynamiques) ;
  assets `resources/{css/vitrine.css,js/vitrine.js}` (entrées Vite séparées).
  - **Mise à niveau design des 6 pages publiques + Contact** (chantier dédié, sans refonte
    de palette ni de typographie — Fraunces/Manrope inchangés) : chaque page dédiée
    (`/formations`, `/marketplace`, `/communaute`) a désormais son propre hero composé
    via un nouveau bloc réutilisable **`.page-hero`** (même langage visuel que `.hero` de
    l'accueil — cadre photo à ombre/bordure identique, padding-top compensant le header
    fixe — mais volontairement plus sobre : une seule image, pas de portrait/couronne
    superposés, qui restent la signature exclusive de l'accueil). `/placali` et
    `/fondateur` avaient déjà un vrai hero (`.placali`/`.founder`) — seul un CTA a été
    ajouté sur `/fondateur` (`.btn.on-dark.ghost`, jusque-là sans aucun bouton).
    - **Ligne de stats réelles dans le hero** (`.page-hero-stats`) sur Formations/
      Marketplace : comptages calculés depuis les collections déjà chargées par le
      contrôleur (`$formations->count()`/`->filter(fn($f)=>$f->isFree())->count()`,
      répartition annonces producteur / boutique officielle sur `$offers`) — jamais un
      chiffre inventé, même esprit que l'indicateur communauté de l'accueil (Phase 11).
    - **Dé-duplication des accroches** : les mini-sections de l'accueil
      (`#formations`/`#marketplace`/`#communaute`) et les pages dédiées lisent les MÊMES
      clés CMS (`formations_section.lead`, etc.) — pour ne pas répéter mot pour mot le
      même paragraphe deux fois de suite dans le parcours d'un visiteur, l'accueil
      n'affiche plus que `eyebrow`+`title` sur ces trois sections (le `lead` complet ne
      vit plus que sur la page dédiée). Décision volontairement limitée à ces 3 sections
      (celles retravaillées cette phase) — `#placali`/`#fondateur` gardent leur traitement
      existant, hors périmètre ici.
    - **États vides** (`.empty-state`, icône + texte + sous-texte, `grid-column:1/-1`
      dans une grille) sur Formations/Marketplace/Communauté/Fondateur(distinctions) — un
      `@empty` explicite plutôt qu'un `<p class="muted">` (classe qui n'existe même pas
      dans `vitrine.css` — l'espace restait donc silencieux, non stylé).
    - **Système de boutons unifié** : `.offer .order` (marketplace) n'utilisait pas
      `.btn` — remplacé par `.btn.sm` (nouveau modificateur de taille) sur
      `marketplace/index.blade.php` **et** `home.blade.php` (même carte). Le bouton
      newsletter du pied de page reste un contrôle distinct (fusionné visuellement dans
      un champ pilule, pas un `.btn` autonome) mais a reçu les mêmes états manquants
      (hover/active/focus-visible/désactivé) directement. `.btn` a gagné `:active`
      (léger enfoncement), `.sm`, et un état `.is-loading` (spinner `currentColor`,
      `animation` ralentie sous `prefers-reduced-motion`) — posé sur les boutons submit
      de Contact/Newsletter via un petit script dans `vitrine.js` (`disabled` + classe +
      spinner injecté au `submit`, jamais de double-soumission possible). `:focus-visible`
      (`outline:2px solid var(--gold)`) est une règle globale sur `.btn`, donc déjà
      correcte sur les sections à fond sombre (`.founder`) sans règle dédiée — vérifié,
      pas juste supposé.
    - **`.reveal`** (scroll-reveal `IntersectionObserver`, `vitrine.js`) existait déjà
      pour la quasi-totalité des sections de l'accueil ; ramené de 700 ms à ~420 ms
      (translateY 26px→18px) pour rester « léger » comme demandé, et étendu aux pages
      dédiées qui ne l'avaient pas du tout. Toujours entièrement désactivé sous
      `prefers-reduced-motion:reduce` (CSS **et** JS — l'IntersectionObserver n'est même
      pas instancié). Hover cohérent (translateY -4px + `var(--shadow)`, même motif que
      `.pillar`/`.course`) désormais aussi sur `.offer` et `.quote`, qui ne l'avaient pas.
    - **Page 404** (`resources/views/errors/404.blade.php`, nouveau — Laravel n'avait que
      la page d'erreur générique du framework) : même layout public, bloc `.error-page`.
    - **Carte de la page Contact** (`/contact`, refondue en 2 colonnes formulaire/
      coordonnées) : **Leaflet auto-hébergé** (`npm install leaflet`, PAS de `<script>`
      CDN — la CSP `script-src` n'a donc pas besoin d'être élargie). Chargement en
      **import dynamique** (`import('leaflet')`/`import('leaflet/dist/leaflet.css')`
      dans `vitrine.js`, uniquement si `#contactMap` existe dans le DOM) plutôt qu'un
      import statique en tête de fichier : Leaflet (~150 Ko JS + ~10 Ko CSS) n'est ainsi
      jamais chargé sur les 5 autres pages vitrine qui n'ont pas de carte — vérifié via
      `manifest.json` après build (chunk séparé `leaflet-src-*.js`/`leaflet-*.css`, pas
      dans les dépendances statiques de l'entrée `vitrine.js`). Les icônes par défaut de
      Leaflet (chemins relatifs qui ne survivent pas au bundling) sont réimportées
      explicitement et injectées via `L.Icon.Default.mergeOptions()`.
      - **CSP non modifiée — vérifié, pas supposé** : les tuiles OpenStreetMap sont des
        `<img>` (`https://tile.openstreetmap.org/...`), déjà couvertes par la règle
        `img-src 'self' data: https: blob:` existante (`SecurityHeaders`). Aucune
        nouvelle entrée `connect-src`/`script-src`/`style-src` n'était nécessaire.
      - **Coordonnées pilotées par le CMS**, jamais codées en dur : `pied.map_lat`/
        `map_lng`/`map_zoom` (nouveaux champs texte, `content-sections.php`) + `pied.city`
        (réutilisé comme adresse affichée, déjà là) + `pied.hours` (nouveau, facultatif).
        Si `map_lat`/`map_lng` sont absents ou non numériques, `contact.blade.php` ne
        rend tout simplement pas `#contactMap` (`$hasMap` calculé côté Blade) — la carte
        est masquée proprement, jamais une tentative d'init Leaflet sur des coordonnées
        invalides. `formations_section`/`marketplace_section` ont aussi gagné un champ
        `image` (hero dédié) — les deux nouveaux fichiers `img/formation-champ.jpg`/
        `img/attieke-marche.jpg` existaient déjà dans `public/img/` (jamais utilisés
        jusqu'ici), pas de nouvel asset à fournir.
      - Bandeau de succès (`session('contact_sent')`) stylé en vrai composant
        (`.alert-ok` avec icône, existait déjà comme classe mais sans icône) plutôt qu'une
        ligne de texte brute.
- **Catalogues publics producteurs / besoins** (`/producteurs`, `/besoins`) : distincts de
  `/marketplace` (qui reste le panneau d'annonces `MarketplaceListing` tel quel) — un annuaire
  structuré et filtrable des `producer_profiles` (vérifiés ou non, dès qu'ils ont ≥1
  `crop_offers` publiée) et des `buyer_needs` ouverts. `App\Livewire\Public\Producers`/`Needs`,
  recherche + filtres avec `#[Url]`/`updating{Prop}()`/`resetPage()` (même schéma que
  `Admin\Members`), pagination Livewire via un gabarit dédié
  `resources/views/vendor/pagination/vitrine.blade.php` (ne pas laisser ces pages hériter du
  gabarit `adm` — `Paginator::defaultView` dans `AppServiceProvider` est celui du back-office,
  passer explicitement `->links('vendor.pagination.vitrine')`). Filtres producteurs : recherche
  (nom/produit), zone, `activity_type`, vérifié, « disponible maintenant » (`available_from`
  nul ou passé) ; filtres besoins : recherche (produit), zone. **Le filtre « note » est affiché
  mais désactivé** (`<select disabled>`, aucun `wire:model`) — les avis n'existent pas avant la
  Phase 8, à brancher à ce moment-là. Visibilité publique centralisée dans des scopes Eloquent
  (jamais un profil/une offre/un besoin d'un compte suspendu, ni une offre/besoin non publié(e)) :
  `CropOffer::scopePublished()` et `BuyerNeed::scopeOpen()` excluent désormais aussi les comptes
  `suspendu` (`whereHas('...user', ...)`) ; `ProducerProfile::scopeActive()` (compte non
  suspendu) et `::scopeVerified()` (`verified_at` renseigné) sont nouveaux. Aucune coordonnée
  personnelle affichée sur ces cartes (§8.2) — seule la zone ; pas de fiche détail. Chaque offre
  listée dans une carte producteur (`.offer-links`) et chaque bouton « Répondre » d'une carte
  besoin (`.order`) pointent désormais vers les routes de mise en relation (voir ci-dessous) —
  `auth`/`producer`/`buyer` font le guest→connexion→retour et l'onboarding manquant
  automatiquement, aucune logique de redirection à coder ici. Cartes réutilisant le style
  `.offer`/`.offers` de la marketplace publique (cohérence visuelle demandée) plutôt qu'une
  nouvelle grille. Pas de throttle sur la recherche (requêtes indexées simples, lecture seule) —
  à revoir si les jointures/filtres deviennent coûteux.
  Nouvelle section CMS bilingue `producteurs_section` (`content-sections.php` /
  `ContentManager::LIST_PATHS`) sur `home.blade.php`, entre `#marketplace` et `#communaute` :
  titres + liste d'opportunités + texte « mise en relation » + boutons CMS « Je suis
  producteur »/« Je suis acheteur » (liens fixes vers `learner.producer`/`learner.buyer` — Laravel
  redirige un invité vers la connexion puis le ramène sur l'URL demandée, pas de logique de
  redirection à coder) + liens « Voir les producteurs »/« Voir les besoins » (`site.producteurs.*`
  dans `lang/{fr,en}/site.php`, structurels donc traduits, contrairement au contenu CMS qui reste
  en français). Liens ajoutés au pied de page (colonne « Explorer ») pour la découvrabilité — le
  menu d'en-tête n'est pas touché (tableau positionnel à 5 entrées fixes, cf. commentaire dans
  `public-layout.blade.php`).
- **Contact / infolettre** : `/contact` stocke en base (`contact_messages`) **puis** envoie
  l'e-mail dans un `try/catch` (aucun prospect perdu si le SMTP tombe). Honeypot `website`
  + `throttle` sur les POST. `sitemap.xml` via `SitemapController`. Le message part vers l'adresse
  configurée dans le CMS (Contenu du site → Pied de page → E-mail) **et en copie (Cc) vers les
  admins actifs** (`User::activeAdminEmails()`, sans doublon si l'admin est déjà le destinataire
  principal) — même mécanique pour `RegistrationLeadMail` (prospects de campagne). Les notifications
  qui ciblent déjà directement l'admin (nouveau membre, paiement à vérifier, nouvelle commande)
  n'ont pas besoin de ce Cc, elles utilisent déjà l'e-mail du compte admin actif comme destinataire.
- **Boîte de réception admin** (`Admin\Messages`, `/admin/messages`) : `contact_messages.status`
  (`nouveau → lu → traite` / `spam`), ouverture = passe en `lu`, **réponse par e-mail depuis
  l'app** (`ContactReplyMail` → `replied_at` + `traite`), note interne, liens mailto/WhatsApp.
  Badge nav = messages `nouveau`. `Admin\Newsletter` (`/admin/infolettre`) : liste,
  (dés)inscription, ajout manuel, **export CSV** (`admin.newsletter.export`). Le ré-abonnement
  via le footer réactive un e-mail désinscrit.
- **Formulaires d'inscription (campagnes réseaux sociaux)** : `RegistrationForm` (une page
  publique par campagne, `/inscription/{slug}`). `Admin\RegistrationForms` (`/admin/inscriptions/
  formulaires`) n'est plus qu'une liste (statut, copier-le-lien, supprimer) ; la création/édition
  se fait sur une page dédiée plein écran **façon Google Forms** — `Admin\RegistrationFormBuilder`
  (`admin.registration-forms.create` / `.edit`, liée par `{form:slug}`), volontairement séparée de
  la liste pour avoir sa propre URL (retour navigateur, partage) plutôt qu'une modale. Barre d'outils
  collante (titre modifiable en ligne, slug généré puis figé dès qu'on le touche, bascule
  brouillon/publié, copier-le-lien, ouvrir en public, bascule aperçu). Les champs à points
  (objectifs, programme, tarif…) sont stockés en base comme du texte brut (`**gras**` /
  `## sous-titre`, consommé par `RegistrationForm::renderLines()`/`renderText()`), mais l'admin ne
  tape jamais cette syntaxe — le composant convertit ce texte en tableau `items` (label/texte/type
  éditables, boutons ajouter/monter/descendre/supprimer via `addItem`/`moveItem`/`removeItem`,
  resynchronisés par le hook `updated()`) et inversement au fil de l'édition
  (`parseItemsFromText()`/`serializeItems()`, partiel réutilisable
  `livewire/admin/partials/registration-form-items.blade.php`). Colonne d'aperçu en direct
  (`previewHtml()`, bascule mobile/web) qui réutilise le vrai template public (`preview: true`
  désarme le `<form>` et affiche un bandeau) dans une `<iframe srcdoc>`.
  `RegistrationLead` (soumissions, suivi manuel dans `Admin\RegistrationLeads` —
  `/admin/inscriptions/prospects`, statuts `nouveau → contacte → inscrit` / `abandonne`, note
  interne, liens mailto/WhatsApp). Champs du formulaire public fixes (identité, contact,
  niveau d'expérience, statut/fonction, tranche d'âge, motivations, moyen de paiement) ; seul
  le contenu (titre, programme, tarif, image, WhatsApp…) est éditable. L'e-mail est facultatif
  (le téléphone suffit à recontacter) ; `RegistrationLead::mailtoLink()` retourne `null` si
  absent. Le champ « Statut/fonction » est un choix fermé (`RegistrationFormController::
  STATUS_FUNCTIONS`) avec option « Autre » + saisie libre (`profession_other`, fusionné dans
  `profession` par le contrôleur, même mécanique que `motivation_other`). Aucun lien avec
  Enrollment/Payment — ce sont des prospects de
  campagnes externes, pas des inscriptions au catalogue interne. `RegistrationLeadMail` notifie
  l'admin par e-mail (`try/catch`, comme le formulaire de contact). Badge nav = prospects `nouveau`.
  - **Section « Votre investissement »** : offre « premiers inscrits » à échéance configurable
    (`registration_forms.early_bird_deadline`, `datetime`) affichant un compte à rebours JS
    (jours/heures/minutes, recalculé côté client depuis `data-deadline` en ISO 8601 — le serveur
    tourne en UTC et Abidjan est UTC+0, donc l'heure saisie dans l'admin (`datetime-local`,
    pas de fuseau) correspond directement à l'heure réelle) ; passé l'échéance, affiche
    automatiquement un message de fin d'offre au lieu d'un compteur négatif. Réservation de place à
    acompte réduit via `deposit_amount` (texte libre, ex. « 50 000 FCFA ») + `deposit_note`
    (conditions du solde/bonus), affichée à côté du paiement intégral (`price_amount`). Les deux
    champs sont facultatifs et par formulaire (aucune date ni aucun montant par défaut) ; tout est
    éditable dans `Admin\RegistrationFormBuilder`, section « Votre investissement ». N'affecte pas
    `RegistrationLead` : le pré-inscrit choisit seulement un `payment_method` dans le formulaire,
    l'arbitrage intégral/réservation se fait ensuite manuellement avec l'équipe (même logique que
    le reste du paiement manuel du site). `early_bird_deadline` est en `dateTime` (pas
    `timestamp`) — MySQL convertit les colonnes `timestamp` selon le fuseau *session* du serveur
    (`SYSTEM`, pas forcément UTC) à l'écriture et à la lecture, ce qui peut faire dériver
    silencieusement l'échéance affichée si ce fuseau serveur change un jour (bascule DST,
    changement d'hébergement) ; `dateTime` stocke la valeur telle quelle, cohérent avec le reste
    de l'app qui raisonne uniquement en UTC (`config('app.timezone')`).
- **Formations** : accès uniquement APRÈS confirmation du paiement (`FormationPolicy@follow`).
- **Annonces producteurs** (`MarketplaceListing`) : `ListingCheckout` → crée une `Order`
  polymorphe (orderable = listing), `payment_mode = direct`, `amount = 0` (« à convenir »).
  Le Roi du Manioc **ne touche pas l'argent** — il notifie le producteur (`ListingOrderMail`)
  + l'admin, historise, et garde un lien WhatsApp vers le vendeur. Suivi dans `Admin\Orders`
  (statuts *nouvelle → en cours → conclue*).
- **Produits (boutique)** : `ShopCheckout` → 2 modes : **`online`** (preuve + vérif admin → commande *à préparer*)
  ou **`on_delivery`** (paiement à la réception → commande directement *à préparer*). Adresse +
  téléphone toujours demandés. `Order` : `reference` (CMD-XXXX), `payment_mode`, `delivery_*`,
  `delivery_fee` (Paramètres), lifecycle `paiement → validee → expediee → livree` (+ `refuse`).
  Le stock `ShopProduct` est **réservé à la création** et **réintégré à l'annulation** (events
  `Order::booted()`). `Admin\Orders` : expédier / livrer / annuler-avec-motif + « Prévenir sur
  WhatsApp » (deep-link gratuit) + e-mails client (`OrderUpdateMail`).
  **Anti-abus livraison** : `users.delivery_strikes` — annulation « client absent » = +1 ;
  à 2 → `User::canPayOnDelivery()` false, checkout limité au paiement en ligne (admin remet à
  zéro dans Membres). Max 3 commandes `on_delivery` ouvertes par client.
- **`php artisan payments:expire-pending`** (cron quotidien) : annule les commandes `paiement`
  (>5 j) et inscriptions `paiement` (>7 j) **sans capture téléversée** → stock rendu, client
  prévenu. Les dossiers avec preuve restent à l'admin.
- **Paiement manuel (anti-fraude)** — voir [[paiement-securite]] :
  - `App\Actions\DeclarePayment` : point d'entrée unique (formation ou produit). Le montant
    ATTENDU est calculé serveur (jamais reçu du client) ; 1 seul paiement `a_verifier` par
    (client, objet) ; compte suspendu bloqué ; rate limit ; capture stockée sur le disque
    **privé** `local` + hash SHA-256.
  - `Payment::runAutoCheck()` : compare le montant déclaré, détecte n° de transaction /
    capture réutilisés et paiements multiples → `check_result['flags']` + `risk` (low/medium/high).
    **Ne débloque jamais rien** — aide au tri.
  - `Payment::confirm($admin)` / `reject($admin, $reason)` : idempotents (no-op si plus
    `a_verifier`), transaction DB, `LogsActivity`. `confirm()` valide l'`Enrollment` ou l'`Order`.
  - Écran admin `Admin\Payments` : verdict + signaux, capture via route gardée
    `payments.proof` (admin **ou** propriétaire), **case « j'ai vérifié la réception »
    obligatoire** avant de confirmer. 4 e-mails (`app/Mail/Payment*`).
- **Back-office = Livewire** dans `app/Livewire/Admin/` (composants *classe*, pas SFC),
  layout `components.layouts.admin`, assets `resources/{css/admin.css,js/admin.js}`.
  Routes nommées `admin.*` sous middleware `['auth','admin']`. Feedback via
  `$this->dispatch('notify', message: …)` (toast JS). Modale réutilisable : `<x-adm.modal>`,
  pastilles : `<x-adm.pill :status="…">`.
- **Compteurs quasi-live (sans websocket)** : la nav latérale est le composant
  `App\Livewire\Admin\Nav` (`<livewire:admin.nav />` dans le layout) — `wire:poll.30s`
  rafraîchit les badges (paiements `a_verifier`, commandes `validee`, messages `nouveau`,
  annonces `en_attente`, posts `signale`). Le tableau de bord fait `wire:poll.60s`.
- **Pagination** : `Members` (25), `Messages` (15), `Newsletter` (30), `Payments` (15),
  `Orders` (20), `ActivityLog` (40) utilisent `WithPagination` + `->paginate()` + `{{ $x->links() }}`.
  Gabarit maison `resources/views/vendor/pagination/adm.blade.php` (défini par défaut dans
  `AppServiceProvider`, styles `.adm-pagination` dans `admin.css`). `updatingSearch` /
  `setFilter` → `resetPage()`. Formations / Boutique / Événements restent non paginés (catalogue borné).
- **Confirmations** : PAS de `wire:confirm` (dialogue navigateur moche). On met
  `data-confirm="Message ?"` (+ `data-confirm-label`, `data-confirm-danger`) sur le
  `<button wire:click>` ; `resources/js/admin.js` intercepte le clic en capture, affiche
  `<x-adm.confirm-dialog>` (dans les 2 layouts) et rejoue le clic à la validation. Même principe
  pour copier un lien dans le presse-papier : `data-copy="valeur"` sur un bouton, intercepté par
  `admin.js` (toast "Lien copié !").
- **CMS "Contenu du site"** : `ContentManager` + schéma `app/Livewire/Admin/content-sections.php`
  (décrit chaque section telle qu'elle est stockée dans `site_contents.data`, càd ce que
  `home.blade.php` consomme, et déclare le type de chaque champ : `text|textarea|html|list|image`).
  Témoignages et distinctions = modèles dédiés édités dans le même écran. `SiteContent::payload()`
  est mis en cache `rememberForever` (une clé par langue, `site_content.{fr,en}`) et invalidé via
  les events Eloquent `saved`/`deleted` (`booted()`) — donc toute écriture doit passer par une
  instance de modèle (`->update()`/`->save()`), **jamais** par `Model::where(...)->update()`
  (bypass les events → site public figé sur l'ancien contenu). Champs `image` : upload direct
  (`WithFileUploads`, disque `public`, dossier `site-content/`, ancien fichier supprimé au
  remplacement — même pattern que `RegistrationFormBuilder`/`cover_image_path`), propriété miroir
  `imageFiles` (mêmes chemins que `data`), traité par `storeUploadedImages()` avant la validation
  dans `save()`. Réseaux sociaux (section `pied`) : `facebook`/`instagram`/`tiktok`/`youtube`/
  `whatsapp`/`linkedin`, rendus dans `public-layout.blade.php` avec icônes aux couleurs de marque,
  masqués individuellement si vides (`whatsapp` accepte un numéro brut ou un lien complet, normalisé
  en `https://wa.me/...`).
  - **Contenu bilingue FR/EN** : chaque champ localisable (tous les types sauf `image`) est stocké
    en base comme `{"fr": "...", "en": "..."}` — y compris à l'intérieur d'un `repeater` (ex.
    `piliers.cards[].text`). `SiteContent::localizeSection()` résout cette forme brute vers la
    langue courante avec **repli automatique sur le français** si la traduction anglaise est vide
    (`filled($value[$locale])`), donc une section jamais traduite reste lisible en anglais. Écran
    admin : `cms-field.blade.php` affiche une paire de champs FR/EN côte à côte (`.bilingual-grid`)
    pour tout ce qui n'est pas une image ; les témoignages/distinctions ont leurs propres colonnes
    `quote_en`/`author_role_en` et `title_en`/`description_en` (accesseur `->localized('champ')`
    sur `Testimonial`/`Award`, même logique de repli). Langues disponibles centralisées dans
    `config/locales.php` (`supported`/`default`), lu par `SetLocale`, la route `locale.switch` et
    `SiteContent`. `SiteContent::wrapMonolingual($data, $key)` convertit une donnée "à plat" (une
    seule langue) vers la forme bilingue en s'appuyant sur `content-sections.php` — utilisé par
    `SiteContentSeeder` et par la migration ponctuelle `2026_09_13_000002_localize_site_content_data`
    qui a converti les données déjà en base. Ne pas revenir à un stockage "une seule langue" pour un
    champ CMS sans repasser par ces mêmes helpers, sous peine de corrompre la lecture bilingue.
- **Espace apprenant** : `app/Livewire/Learner/`, layout `components.layouts.learner`
  (même CSS/JS que l'admin). Formations gratuites → inscription immédiate (`Enrollment` validée) ;
  premium → `Checkout` crée `Enrollment` (statut `paiement`) + `Payment` (`a_verifier`) —
  la confirmation admin + l'upload de preuve arrivent en Phase 5. Progression =
  `LessonProgress` + `Formation::progressFor($user)`. `FormationPolicy` (`follow`/`enroll`)
  garde l'accès aux cours.
- **Espaces producteur / acheteur** : `producer_profiles` / `buyer_profiles`, superposés à un
  compte existant (FK `user_id` unique, `cascadeOnDelete`) — `users.role`/`status` ne changent
  pas, un même compte peut être apprenant + producteur + acheteur en même temps
  (`User::producerProfile()`/`buyerProfile()` (`hasOne`), `isProducer()`/`isBuyer()`).
  Onboarding + édition sur une seule page/composant par profil (`App\Livewire\Learner\
  ProducerProfile`/`BuyerProfile`, `/mon-espace/producteur`/`acheteur` — même route pour créer
  et modifier, le composant regarde si `producerProfile`/`buyerProfile` existe déjà). CTA
  d'activation sur `/mon-compte` (`Account\Settings`) ; dans la nav du layout learner, le groupe
  « Mon royaume » affiche « Profil producteur » (+ « Mes offres » une fois activé) / « Espace
  acheteur » si le profil existe, sinon « Devenir producteur/acheteur » (même route, badge
  `Activer`). `activity_type` (`App\Enums\ActivityType`) reprend le vocabulaire de
  `MarketplaceListing.type` (Récolte/Bouture/Transformé/Intrant) mais stocké comme enum PHP à
  valeurs slug, pas les libellés bruts utilisés par `MarketplaceListing`. `buyer_type` :
  `App\Enums\BuyerType`. Logo producteur : même pattern d'upload que
  `ContentManager::storeUploadedImages()` (disque `public`, dossier `producer-profiles/`).
  `verified_at`/`verified_by` (`ProducerProfile`) volontairement absents du `$fillable` — jamais
  renseignables par l'utilisateur, réservés à un `forceFill` admin (vérification producteur,
  Phase 8, pas encore implémentée ; `ProducerProfile::isVerified()` existe déjà et servira au
  badge « Producteur vérifié ✅ » côté public en Phase 4). Middlewares persistants Livewire
  `producer`/`buyer` (`EnsureUserIsProducer`/`EnsureUserIsBuyer`, calqués sur
  `EnsureUserIsActive`) — ne gardent volontairement pas les routes d'onboarding elles-mêmes
  (sinon boucle de redirection), mais gardent `/mon-espace/producteur/offres*`,
  `/mon-espace/acheteur/besoins*` et les routes de mise en relation (`offres/{offer}/contacter`,
  `besoins/{need}/repondre`, `demandes`) — voir ci-dessous.
- **Offres producteur** (catalogue interne, alimente la recherche publique et la mise en
  relation ci-dessous) : `crop_offers` (FK `producer_profile_id`, `cascadeOnDelete`, `SoftDeletes`)
  + `crop_offer_photos` (table enfant plutôt qu'un JSON, max 5 photos imposé côté composant,
  pas en base). `unit` : `App\Enums\CropUnit` (kg/sac/tonne/unite) ; `status` :
  `App\Enums\CropOfferStatus` (brouillon/publiee/indisponible/archivee) — distinct du booléen
  `is_available`, basculé sans confirmation (`Producer\Offers::toggleAvailability`, action non
  destructive) pour signaler une rupture temporaire sans dépublier l'offre. `App\Livewire\
  Producer\Offers` (liste paginée, 9/page, `/mon-espace/producteur/offres`) + `Producer\
  OfferForm` (création/édition sur la même page, `/offres/creer` et `/offres/{offer}/modifier` —
  redirige vers `.edit` après création, comme `RegistrationFormBuilder`), upload multiple
  (`WithFileUploads` + `wire:model` sur un tableau, `newPhotos.*`) plafonné à 5 photos au total
  (existantes + nouvelles) par une règle de validation `Closure` sur `newPhotos`, une photo à la
  fois supprimable immédiatement (`removeExistingPhoto`, sans passer par `save()`). Les
  coordonnées du producteur ne figurent jamais sur une offre — seule `location` (zone) est
  stockée, jamais un contact direct (§8.2). `CropOfferPolicy` (`update`/`delete`, propriétaire ou
  admin). Rate limit création : `throttle:6,1` (même famille que `contact.send`) sur la route
  `/offres/creer`, ajouté à `Livewire::addPersistentMiddleware` via
  `Illuminate\Routing\Middleware\ThrottleRequests::class` pour rester actif sur les `wire:click`
  de la page (pas seulement le chargement initial) — même mécanique que `EnsureUserIsActive`.
- **Besoins acheteur** (symétrique des offres producteur ci-dessus, même catalogue interne /
  mise en relation) : `buyer_needs` (FK `buyer_profile_id`,
  `cascadeOnDelete`, `SoftDeletes`), pas de table de photos. `unit` réutilise `App\Enums\
  CropUnit` (même vocabulaire des deux côtés du marché). `frequency` est une simple colonne
  string (`ponctuel`/`recurrent`) — volontairement PAS un enum PHP dédié malgré la convention
  habituelle : deux valeurs, jamais réutilisées ailleurs, validées par `Rule::in()` dans
  `Buyer\NeedForm`. `status` : `App\Enums\BuyerNeedStatus` (ouvert/satisfait/expire/ferme).
  `App\Livewire\Buyer\Needs` (liste paginée, 9/page, `/mon-espace/acheteur/besoins`) +
  `Buyer\NeedForm` (création/édition, même schéma de routes/redirection que `Producer\
  OfferForm`), `BuyerNeedPolicy` (`update`/`delete`, propriétaire ou admin), même rate limit
  `throttle:6,1` sur `/besoins/creer`. `php artisan needs:expire-outdated` (cron quotidien,
  `routes/console.php`) : passe en `expire` les besoins `ouvert` dont `wanted_date` est
  dépassée — mise à jour en masse (`BuyerNeed::where(...)->update(...)`, pas d'e-mail ni de
  stock à réintégrer contrairement à `payments:expire-pending`, donc pas besoin du traitement
  instance par instance). Aucune coordonnée personnelle sur un besoin — pas de route publique
  de détail (le catalogue `/besoins` liste les besoins, la mise en relation se fait depuis là,
  voir ci-dessous).
- **Mise en relation** (§14 — « le cœur de la V1 ») : `connection_requests`, machine à états
  `App\Enums\ConnectionRequestStatus` (chemin nominal `en_attente → acceptee → negociation →
  proposition → collaboration_confirmee` ; `refusee`/`annulee` = issues négatives terminales).
  Exactement une de `crop_offer_id`/`buyer_need_id` (jamais les deux, jamais aucune) — vérifié
  dans `ConnectionRequest::booted()` (`creating`), pas par contrainte SQL. `producer_profile_id`/
  `buyer_profile_id` toujours résolus et stockés à la création (simplifie les boîtes de
  réception, pas besoin de traverser `crop_offer.producerProfile` à chaque requête).
  - **`refuse()` vs `cancel()`, deux intentions différentes** : `refuse()` = l'une des deux
    parties décline/met fin (possible depuis `en_attente` — mais seulement pour le
    *destinataire* — puis `acceptee`/`negociation`/`proposition`, n'importe quelle partie).
    `cancel()` = le *demandeur* retire sa propre demande avant toute réponse (possible
    uniquement depuis `en_attente`). Ce sont deux méthodes distinctes précisément pour garder
    cette nuance dans l'historique, plutôt qu'un `refuse()` unique avec une logique d'acteur
    conditionnelle à l'état — `cancel()` n'était pas dans la liste de méthodes du cahier des
    charges mais `annulee` y figurait sans point d'entrée : sans elle, ce statut n'était jamais
    atteignable.
  - **Gardes en double couche, jamais divergentes** : chaque transition a un prédicat
    `canXxxBy(User $actor): bool` sur le modèle (ex. `canBeAcceptedBy`), utilisé à la fois par
    `ConnectionRequestPolicy` (couche UI, `$this->authorize(...)` dans `Connect\Show`) et par la
    méthode de transition elle-même (`accept()`, etc.) — même esprit que
    `Payment::confirm()`/`reject()` : **idempotent**, renvoie `false` sans lever d'exception si
    l'état ou l'acteur ne correspond pas (jamais de `abort`/exception dans le modèle lui-même).
  - **Stepper vertical** (`App\Livewire\Connect\Show`, `/mon-espace/demandes/{connectionRequest}`,
    écran le plus soigné de la V1) : au plus une étape « active » à l'écran.
    `ConnectionRequest::furthestHappyPathIndex()` détermine jusqu'où colorer le stepper en
    « fait » — si le statut courant est dans le chemin nominal, c'est simplement sa position ;
    si `refusee`/`annulee`, déduit de la dernière entrée `LogsActivity`
    (`properties['old']['status']`, fiable ici car `refuse()`/`cancel()` sont les seuls points
    d'entrée qui posent ces statuts) plutôt que d'ajouter une colonne dédiée. Le même journal
    `LogsActivity` (`logOnly(['status'])`) sert aussi tel quel d'historique affiché sous le
    stepper — pas de table d'historique séparée.
  - **Amorce du parcours** : les catalogues publics Phase 4 restent le seul point d'entrée
    (`/producteurs` liste chaque offre d'un producteur avec un lien direct
    `learner.buyer.offers.contact` ; `/besoins` a un bouton `learner.producer.needs.respond` par
    carte) → `Connect\RequestOffer`/`RequestNeed` (message facultatif) → `App\Actions\
    CreateConnectionRequest` (point d'entrée unique, même esprit que `DeclarePayment` : compte
    suspendu bloqué, `RateLimiter` par utilisateur, **pas deux demandes OUVERTES** — statuts hors
    `refusee`/`annulee`/`collaboration_confirmee` — entre le même producteur et le même acheteur
    sur la même offre/le même besoin ; redevient possible après refus/annulation) → redirige vers
    `requests.show`. Auto-mise-en-relation bloquée (`abort_if` dans `mount()`) : un producteur ne
    peut pas contacter sa propre offre, un acheteur ne peut pas répondre à son propre besoin.
  - Boîtes de réception `App\Livewire\Producer\Requests` / `Buyer\Requests`
    (`/mon-espace/{producteur,acheteur}/demandes`, filtre par statut façon `Admin\Members`).
  - **Notifications branchées en Phase 9** : chaque transition dispatche un event dédié
    (`App\Events\ConnectionRequest{Created,Accepted,Refused,Cancelled,MovedToNegotiation,
    Proposed,Confirmed}`) ; seuls `Created`/`Accepted`/`Proposed`/`Confirmed` ont un
    listener (les 4 événements couverts par §24 côté mise en relation) — `Refused`/
    `Cancelled`/`MovedToNegotiation` restent de purs points d'extension sans consommateur,
    volontairement (le cahier des charges §24 énumère 8 événements précis, pas « toute
    transition » — cf. Notifications ci-dessous).
- **Messagerie interne** (§16, « fonctionnalité stratégique ») : `conversations` (1 par
  `connection_requests`, créée **à la volée au premier message** — pas à la création de la
  demande, pour éviter des lignes vides) + `conversation_messages` (`sender_id`, `body`,
  `contains_flagged_content`, `flagged_patterns` JSON). Bulles classiques sous le stepper de
  `Connect\Show` (`App\Livewire\Connect\Conversation`, intégré en composant enfant
  `<livewire:connect.conversation>`), actualisées par `wire:poll.5s` (pas de websocket, même
  principe que les badges admin quasi-live).
  - **`App\Support\ContactDetector`** (classe pure, testée isolément dans `tests/Unit/
    ContactDetectionTest.php`, sans dépendance Laravel) : détecte téléphone (par comptage de
    chiffres après nettoyage des séparateurs — 8 à 15 chiffres, couvre local/+225/00225 sans
    énumérer chaque format), e-mail, liens wa.me/whatsapp, liens facebook/instagram, et
    quelques tournures françaises (« appelez-moi », « mon whatsapp »…). **Aide au tri, jamais
    un blocage** (même philosophie que `Payment::runAutoCheck()`, cf. cahier des charges §16
    « Important » — aucune regex ne peut garantir une détection à 100 %) : le message est
    **toujours enregistré tel quel** dans `body` (traçabilité admin), seul l'AFFICHAGE aux
    deux parties est masqué — `ConversationMessage::displayBody($unmasked = false)` réapplique
    `ContactDetector::mask()` sur `body` à partir des `flagged_patterns` stockés (rien de
    masqué n'est jamais écrit en base). Les tournures détectées (`formule_contact`) ne sont
    volontairement PAS masquées à l'affichage (aucune coordonnée réelle à cacher dans « appelez-
    moi » lui-même) — seules les catégories qui contiennent une vraie donnée le sont.
  - `App\Actions\SendConversationMessage` (point d'entrée unique, même famille que
    `DeclarePayment`/`CreateConnectionRequest`) : compte suspendu bloqué, `RateLimiter` par
    utilisateur, taille max (`SendConversationMessage::MAX_LENGTH`, 2000), crée la conversation
    si besoin (`firstOrCreate`) puis fait trancher `ConversationPolicy::send()` via
    `Gate::forUser($sender)->authorize(...)`.
  - **Premier cas où le contournement admin (`Gate::before` dans `AppServiceProvider`) est
    volontairement exclu** : un administrateur a un accès lecture seule pour modérer (§16),
    jamais le droit d'écrire à la place d'une des deux parties. `Gate::before` refuse
    maintenant de court-circuiter spécifiquement l'ability `send` sur un `Conversation` (elle
    laisse alors `ConversationPolicy::send()` répondre normalement, qui exige
    `connectionRequest->isParty($user)` — vrai pour un admin uniquement s'il est *aussi*, par
    ailleurs, l'une des deux parties réelles). Si une future policy a besoin de la même
    exclusion, suivre ce même motif (`$ability === '...' && $arguments[0] instanceof ...`)
    plutôt que de complexifier `Gate::before` avec une liste d'exceptions génériques.
  - **Modération admin** : `App\Livewire\Admin\Conversations` (`/admin/conversations`, lecture
    seule, liste toute conversation ayant ≥ 1 message signalé) + badge quasi-live dans
    `Admin\Nav` (« Conversations signalées », `wire:poll.30s` existant) compté uniquement à
    partir de `Conversation::FLAG_THRESHOLD` (3) messages signalés sur la même conversation —
    volontairement un seuil différent de la liste (qui montre tout signal dès le premier, pour
    laisser l'admin consulter) afin que le badge reste un signal de bruit faible.
    `Conversation::flaggedMessagesCount()`/`needsModeration()` : calculés à la volée
    (`COUNT` sur `conversation_messages`), aucun compteur dénormalisé en base — évite un champ
    qui pourrait diverger du contenu réel.
  - Échappement HTML garanti par défaut (Blade `{{ }}`, jamais `{!! !!}` sur un message) —
    aucun traitement supplémentaire nécessaire, testé explicitement (`assertSee()` sur un
    message contenant `<script>`, qui n'est trouvé que sous sa forme échappée).
- **Collaboration** (§18/§19 — formalise l'accord une fois `collaboration_confirmee`) :
  `collaborations` (1 par `connection_requests`), `collaboration_payments`,
  `collaboration_deliveries`. **Décision d'architecture à respecter** : ne réutilise
  jamais `Order`/`Payment` — ces modèles encaissent pour la plateforme et seul l'admin y
  confirme ; ici c'est un flux **pair-à-pair** où l'acheteur déclare et le **producteur**
  confirme, Le Roi du Manioc ne touche pas l'argent (§18). `method` sur
  `collaboration_payments` est un texte libre (« Mobile Money », « espèces »…), pas
  `App\Enums\PaymentMethod` (canaux vers la plateforme — sémantique différente).
  - **Création automatique, pas manuelle** : `App\Livewire\Connect\Show::confirmCollaboration()`
    crée la `Collaboration` (+ sa `CollaborationDelivery`, `prevue`) dès que la transition
    réussit — `agreed_product`/`quantity`/`unit` repris de l'offre ou du besoin d'origine,
    `terms_note` du `message_initial` ; `firstOrCreate` (idempotent, jamais de doublon même
    en double-clic). Aucun de ces champs n'est modifiable ensuite dans cette V1 (pas
    demandé — la négociation reste dans la messagerie, Phase 6).
  - **`livraison_confirmee` n'est jamais une valeur persistée** : le dernier maillon de la
    livraison (`receptionnee`) fait directement passer `Collaboration.status` à `terminee`
    (rien ne distingue les deux dans cette V1). Le stepper (même algorithme `furthestIndex`/
    `currentIndex` que `Connect\Show`, Phase 5) l'affiche quand même comme « acquis » dès que
    `terminee` est atteint, purement via la position de `terminee` dans
    `CollaborationStatus::happyPath()` — voir `App\Enums\CollaborationStatus` pour le détail.
  - **`refuse`/`cancel` d'un paiement, deux issues différentes** : `contestPayment()` (producteur,
    « je n'ai rien reçu ») **ne bascule PAS en `litige`** — retour direct à `en_cours` pour que
    l'acheteur redéclare (`CollaborationPaymentStatus::Conteste` reste sur CE paiement précis,
    dans son propre historique). `litige` est réservé exclusivement à `markDisputed()`, un
    pouvoir **admin uniquement** (§ Sécurité : « jamais confirmer à la place d'une partie ») —
    motif journalisé via le helper `activity()` de spatie/activitylog directement (pas de
    colonne `dispute_reason` en base, volontairement absente du schéma demandé).
  - **Deuxième exclusion du contournement admin** (après `Conversation::send`, Phase 6) :
    `Gate::before` (`AppServiceProvider`) exclut maintenant aussi `declarePayment`,
    `confirmPayment`, `contestPayment`, `markDeliveryStep` et `cancel` quand le sujet est une
    `Collaboration` — l'admin garde un accès lecture seule (+ `markDisputed`, son seul pouvoir
    propre). Toute nouvelle ability du même genre (une partie agit, jamais l'admin à sa place)
    doit suivre exactement ce motif (`$ability === '...' && $subject instanceof ...`).
  - Écran « Ma collaboration » (`App\Livewire\Collaboration\Show`,
    `/mon-espace/collaborations/{collaboration}`, gardé par `CollaborationPolicy`, jamais par
    `producer`/`buyer` — un admin doit pouvoir l'ouvrir en lecture seule) : stepper global
    (même langage visuel que Phase 5) + 3 blocs Accord/Paiement/Livraison, chacun avec ses
    propres actions contextuelles. `Connect\Show` affiche un bandeau « Voir ma collaboration →
    » dès que `collaboration` existe. Formulaire de déclaration = montant, moyen (texte libre)
    et note facultative, **seulement** — jamais de champ ressemblant à un vrai formulaire de
    paiement (pas de « numéro de carte ») pour ne pas suggérer une sécurité bancaire qui
    n'existe pas ; l'avertissement légal du §19 est affiché à la fois à la déclaration et à la
    confirmation.
- **Évaluations & vérification producteur** (§21/§23) : `reviews` (unique
  `(collaboration_id, rater_id)` — une fois par partie et par collaboration), possible
  uniquement si `collaboration.status === terminee`. `direction`
  (`App\Enums\ReviewDirection`) a des critères différents selon qui note qui
  (`ReviewDirection::criteria()`) : acheteur→producteur = qualité/quantité/respect des
  engagements/ponctualité/communication (5) ; producteur→acheteur = respect des
  engagements/paiement/communication/ponctualité (4). `App\Actions\SubmitReview` ne garde
  que les clés de critère attendues pour la direction déduite (jamais reçues du client) et
  rejette si une clé manque — empêche l'injection d'une clé arbitraire dans le JSON.
  **Immuable** : aucune méthode d'édition/suppression nulle part dans l'app (§ Sécurité).
  - **Troisième exclusion du contournement admin** (après `Conversation::send` Phase 6 et
    les actions `Collaboration` Phase 7) : `Gate::before` exclut `Review::create` — un
    administrateur n'est jamais partie à une collaboration, il ne doit jamais pouvoir
    déposer un avis. Particularité : c'est la seule ability de la liste appelée avec
    `authorize('create', [Review::class, $collaboration])` (création sans instance),
    donc `$arguments[0]` reçu par `Gate::before` est le **nom de classe** (string), pas
    une instance — comparaison `$subject === Review::class`, pas `instanceof`
    (voir le commentaire dans `AppServiceProvider::boot()` qui explique pourquoi, via
    `Gate::raw()`/`callBeforeCallbacks()`).
  - `ProducerProfile::receivedReviews()` : `hasMany(Review::class, 'ratee_id', 'user_id')`
    — pas de FK directe (`reviews.ratee_id` référence `users.id`, pas
    `producer_profiles.id`), d'où ces clés personnalisées plutôt qu'une relation standard.
    `averageRating()`/`reviewsCount()` ne comptent que les avis **reçus en tant que
    producteur** (`direction = acheteur_vers_producteur`) — un avis qu'un producteur
    dépose sur un acheteur n'affecte jamais sa propre moyenne. Sur une liste (catalogue
    public), préférer `withAvg('receivedReviews as avg_rating', 'rating')` +
    `withCount(... as reviews_count)` à ces deux méthodes (qui interrogent la base à
    chaque appel) pour éviter le N+1.
  - **⚠️ Filtre « note » sur `/producteurs`** (`Public\Producers`, branché à cette phase) :
    n'utilise **jamais** `having()` sur l'alias `withAvg` — fonctionne en MySQL mais
    provoque `HAVING clause on a non-aggregate query` sous SQLite (moteur des tests).
    N'utilise pas non plus `whereRaw('... >= ?', [$floatValue])` seul : un float PHP lié
    tel quel échoue silencieusement la comparaison sous SQLite (la sous-requête ne
    remonte aucune ligne même quand la moyenne dépasse le seuil réellement). La forme qui
    marche identiquement sur les deux moteurs : sous-requête corrélée dans un `whereRaw`
    comparée à `CAST(? AS DECIMAL(4,2))` — **la précision `(4,2)` est obligatoire**,
    `CAST(? AS DECIMAL)` sans précision arrondit en MySQL (ex. 4.5 → 5), ce qui rendrait
    le seuil « 4,5★ et plus » plus strict que prévu. Revalider ce même piège si un futur
    filtre numérique similaire (ex. sur `/besoins`) doit comparer une moyenne calculée à
    un seuil.
  - **Vérification producteur** (§23) : `ProducerProfile::verify()`/`rejectVerification()`
    — idempotentes comme `Payment::confirm()`, pas de policy dédiée (protégées comme le
    reste du back-office par le middleware `admin`, même famille qu'`Admin\Members`).
    `rejectVerification()` **révoque** une vérification existante (`verified_at`/
    `verified_by` remis à `null`) plutôt que d'être un no-op définitif — donne un vrai
    second usage à la méthode (ex. producteur signalé après coup), pas juste l'inverse de
    `verify()`. `ProducerProfile` utilise maintenant `LogsActivity`
    (`logOnly(['verified_at', 'verified_by'])`, même pattern que `Payment`). Écran
    `Admin\ProducerVerification` (`/admin/producteurs`) : case « j'ai vérifié l'identité »
    obligatoire avant le bouton Vérifier (`x-data`/`x-model`/`x-bind:disabled` Alpine,
    **exactement** le motif de `Admin\Payments` — case côté client, jamais un champ
    Livewire). Badge quasi-live dans `Admin\Nav` (nombre de profils `verified_at` null,
    pas de policy ni de filtre d'activité supplémentaire — volontairement simple).
- **Notifications** (§24, Phase 9) : système natif `Illuminate\Notifications` — table
  standard `notifications` (`DatabaseNotification`), pas de table maison, `User` a
  `Notifiable` depuis le début du projet. 8 événements couverts, PAS « toute transition
  possible » (décision volontaire, cohérente avec « sans sur-notifier » du §24) :
  nouvelle demande (`ConnectionRequestCreated`), demande acceptée (`Accepted`),
  proposition reçue (`Proposed`), collaboration confirmée (`Confirmed`), paiement déclaré
  (`CollaborationPaymentDeclared`), étape de livraison franchie
  (`CollaborationDeliveryStepMarked`), nouvelle évaluation (`ReviewSubmitted`), nouveau
  message (`ConversationMessageSent`) — `Refused`/`Cancelled`/`MovedToNegotiation`
  (Phase 5) restent volontairement sans listener. `ConversationMessageSent`/
  `CollaborationPaymentDeclared`/`CollaborationDeliveryStepMarked`/`ReviewSubmitted`
  étaient des points d'extension laissés en Phases 6/7/8 (events déjà présents dans le
  modèle mais jamais dispatchés) — branchés ici sans toucher aux garde-fous existants.
  - **Un listener Laravel dédié par event** (`App\Listeners\Send*Notification[s]`),
    auto-découverts (pas d'`EventServiceProvider` dans ce projet — `handle()` typé
    suffit, vérifié via `php artisan event:list`). Chaque listener résout le
    **destinataire correct** (celui qui n'a pas agi, jamais l'auteur de l'action) via un
    helper `otherParty(User $actor): User` sur `ConnectionRequest` **et** sur
    `Collaboration` (deux implémentations séparées, chacune adaptée à sa propre notion de
    « parties » — `requester`/`receiverUser()` vs `producerProfile.user`/
    `buyerProfile.user`) ; `ConnectionRequestAccepted`/`CollaborationPaymentDeclared`
    n'ont pas besoin de cet arbitrage (acteur toujours déterminé par la garde de la
    transition — seul le destinataire peut accepter, seul l'acheteur peut déclarer).
  - **In-app systématique, e-mail seulement pour 3 événements à fort enjeu** (nouvelle
    demande, collaboration confirmée, paiement déclaré) — 3 classes `Mail` dédiées
    (`App\Mail\NewConnectionRequestMail`/`CollaborationConfirmedMail`/
    `CollaborationPaymentDeclaredMail`, même nommage que `Payment*Mail`), envoyées dans un
    `try/catch` avec `report($e)`, **exactement le motif de `DeclarePayment::notify()`** —
    ne bloque jamais la transition métier si le SMTP tombe (vérifié en test via
    `Mail::shouldReceive('to')->andThrow(...)`, et en smoke-test réel contre le SMTP de
    prod : `Mail::to()->send()` fonctionne bien de bout en bout). Rien n'est envoyé si le
    destinataire n'a pas d'e-mail (compte téléphone seul) — même garde que partout
    ailleurs (§ Comptes).
  - **Sécurité e-mail (cohérent avec la Phase 6)** : le corps d'un e-mail entre les deux
    parties ne contient **jamais** de coordonnée personnelle de l'autre partie (ni
    téléphone, ni e-mail) — seulement un nom et un renvoi (`<x-mail::button>`) vers la
    plateforme, testé explicitement (`assertStringNotContainsString` sur l'e-mail/le
    téléphone de l'autre partie, sur le HTML rendu de chaque Mailable).
  - **Cloche** (`App\Livewire\Learner\NotificationBell`, `<livewire:learner.notification-
    bell />` dans le topbar du layout learner) : même pattern de compteur quasi-live que
    `Admin\Nav` (`wire:poll.30s`, pas de websocket). `markAsRead($id)`/`markAllAsRead()`
    scoping toujours par `auth()->user()->notifications()` — un utilisateur ne peut
    jamais marquer comme lue la notification d'un autre (testé). Dropdown ouverte/fermée
    en Alpine côté client (`x-data`/`@click.outside`, Alpine embarqué par
    `@livewireScripts`, pas d'import séparé nécessaire) ; 10 notifications les plus
    récentes affichées, clic = `markAsRead` + navigation vers `data['url']`.
  - `App\Notifications\*` — chaque classe a `via() => ['database']` uniquement (le canal
    `mail` natif n'est PAS utilisé : les 3 e-mails à fort enjeu passent par des Mailables
    dédiées séparées, pas par `toMail()`, pour garder le contrôle fin du contenu et de la
    politique d'envoi). `toDatabase()` renvoie toujours la même forme
    `['type','title','message','url']`, consommée telle quelle par la cloche.
    `ConnectionRequest::productLabel()` (offre ou besoin) factorise le libellé affiché,
    réutilisé par plusieurs notifications/e-mails.
- **Supervision admin de la mise en relation** (§25/§37, Phase 10) : nouveau groupe de nav
  « Mise en relation » (`Admin\Nav`) — vue d'ensemble chiffrée + 6 écrans de supervision,
  tous protégés par le seul middleware `admin` (aucune policy dédiée, même raisonnement
  que `MarketplaceModeration`/l'ancien `ProducerVerification` : ces écrans sont purement
  administratifs, aucune des policies Connect n'expose d'ability `viewAny`).
  - **`Admin\Producers`** (`/admin/producteurs`) remplace et étend l'ancien
    `Admin\ProducerVerification` (Phase 8, supprimé) : même vérification/révocation
    (`ProducerProfile::verify()`/`rejectVerification()`), plus une recherche
    (business_name/zone) et un lien vers **`Admin\ProducerShow`** (`/admin/producteurs/
    {producerProfile}`, nouvelle fiche détail — profil, vérification, offres, demandes et
    collaborations récentes, chacune reliée aux écrans partagés `learner.requests.show`/
    `learner.collaborations.show` plutôt que dupliqués, cf. point suivant). Tests fusionnés
    dans `ConnectProducersTest.php` (l'ancien `ProducerVerificationTest.php` a été
    supprimé, pas doublé).
  - **Réutilisation délibérée des écrans partagés** : `Connect\Show`
    (`learner.requests.show`) et `Collaboration\Show` (`learner.collaborations.show`)
    autorisent déjà `view` via le contournement générique admin de `Gate::before`
    (seules les *transitions* de parties en sont exclues) — donc `Admin\ConnectionRequests`
    et les liens « Voir » d'`Admin\Collaborations`/`Admin\ProducerShow` pointent vers ces
    mêmes écrans plutôt que de reconstruire une fiche détail redondante. Signaler un litige
    a lui aussi déjà son propre flux sur `Collaboration\Show` (`isViewerAdmin` +
    `markDisputed()`, en place depuis la Phase 7) — non dupliqué ici.
  - **`Admin\ConnectionRequests`** (`/admin/demandes`) : supervision **passive** — aucune
    action de mutation, uniquement liste + filtre par statut (tous les cas de
    `ConnectionRequestStatus`, même gabarit d'onglets que `Producer\Requests`/
    `Buyer\Requests`) + lien vers le détail partagé.
  - **`Admin\Collaborations`** (`/admin/collaborations`, onglet par défaut `litige`) : vue
    d'ensemble + **résolution** des litiges — inédit avant cette phase.
    `Collaboration::resolveDispute(User $admin, string $resolution): bool` est le pendant
    symétrique de `markDisputed()` (même garde `canResolveDisputeBy`, même journalisation
    via `activity()` sans colonne dédiée) mais remet `en_cours`, **jamais** `terminee` —
    ce n'est pas à l'admin de décider que les parties se sont entendues, seulement de
    débloquer la machine à états pour qu'elles reprennent la main. `CollaborationPolicy::
    resolveDispute()` ajouté par cohérence (même si, comme `markDisputed`, aucun
    `authorize()` n'est appelé depuis le Livewire — la garde `canResolveDisputeBy` suffit,
    même choix que `ProducerVerification`).
  - **`Admin\CropOffers`/`Admin\BuyerNeeds`** (`/admin/offres`, `/admin/besoins`) :
    contrairement à `MarketplaceListing`, une offre/un besoin n'a pas de file d'attente à
    valider (publication directe par le producteur/l'acheteur) — le seul pouvoir admin est
    de retirer un contenu problématique (`archivee`/`ferme`) et de le restaurer. Mise à
    jour directe du statut (`->update()`), pas de méthode dédiée sur ces deux modèles :
    même choix que `MarketplaceModeration` (pas de state machine idempotente ici).
  - **`Admin\Buyers`** (`/admin/acheteurs`) : pas de vérification d'identité côté acheteur
    (aucun badge public équivalent) — la seule action propre est la suspension du compte,
    via exactement le même mécanisme que `Admin\Members::toggleSuspend()` (bascule
    `User.status`, mêmes garde-fous anti-verrouillage — auto-suspension et dernier admin
    actif — appliqués même si un acheteur n'est jamais admin en pratique, coût nul).
  - **`Admin\ConnectStats`** (`/admin/mise-en-relation`) : composant à part plutôt qu'une
    extension d'`Admin\Dashboard` (déjà chargé), même esprit « agrégats seuls, pas de
    filtres/pagination ». **`Collaboration::estimatedValue(): int`** — à l'origine (Phase 6-10)
    `agreed_price_total` n'était renseigné par aucun écran et valait donc toujours `null` en
    pratique, d'où le repli sur le prix/budget indicatif (`price_indicative`/
    `budget_indicative`) de l'offre ou du besoin d'origine. **Depuis la Phase 14** (carte de
    proposition structurée dans le chat, cf. plus bas), une proposition peut porter un prix
    et `ConnectionRequest::createCollaborationAgreement()` le reprend dans
    `agreed_price_total` — donc `estimatedValue()` peut désormais retourner le montant
    RÉELLEMENT négocié plutôt que l'estimé, tout en gardant son repli pour les
    collaborations sans proposition chiffrée. Le nom de la méthode reste « estimated » par
    cohérence historique même si la valeur n'est plus toujours un estimé. Hors périmètre
    (explicite, §37) : pas de tableau de bord BI avancé, seulement les compteurs demandés.
    (`Admin\ConnectStats` lui-même a été supprimé en Phase 16, fusionné dans
    `Admin\Dashboard` — voir plus bas.)
- **Indicateur communauté sur l'accueil** (§8.1, Phase 11) : `HomeController::index()`
  calcule `verifiedProducersCount`/`completedCollaborationsCount` depuis la base
  (`ProducerProfile::verified()->active()->count()` / `Collaboration::where('status',
  'terminee')->count()`) et les passe à `home.blade.php`. **Volontairement masqué tant
  que les deux compteurs sont à zéro** (`@if`) — un site tout juste lancé n'affiche
  jamais « 0 producteur vérifié », ce serait contre-productif. Distinct des « chiffres
  clés » (§ CMS `chiffres`, 4 valeurs texte libres tapées par l'admin) : celui-ci est le
  seul chiffre de la page réellement recalculé à chaque chargement, pas un texte édité à
  la main. Affiché dans la section « Producteurs & acheteurs » (`#producteurs`, pas
  `#communaute`) depuis l'audit design de la Phase 12 ci-dessous — sa vraie place
  thématique, et le seul endroit de l'accueil qui a désormais un vrai traitement « gros
  chiffre » pour ce genre de preuve sociale.
- **Mention légale à l'activation d'un espace producteur/acheteur** (§44, Phase 11) :
  `App\Livewire\Learner\ProducerProfile`/`BuyerProfile` exposent une case `acceptedTerms`,
  **obligatoire uniquement à la toute première activation** (`$wasNew = ! $profile->exists`
  avant validation — la règle `accepted` n'est ajoutée à la validation que dans ce cas,
  jamais redemandée sur une simple édition ensuite). À l'acceptation,
  `terms_accepted_at` (nouvelle colonne, `producer_profiles`/`buyer_profiles`,
  volontairement absente du `$fillable` comme `verified_at` — écrite uniquement via
  `forceFill()` dans le composant, jamais reçue telle quelle du client) est posée à
  `now()` : une case cochée sans trace horodatée ne vaudrait rien en cas de litige. Les
  deux cases pointent vers `route('legal.notice')`/`route('legal.privacy')`
  (`/mentions-legales`, `/politique-de-confidentialite`, `PageController::legalNotice()`/
  `privacyPolicy()`), dont le contenu est une **nouvelle section CMS `legal`**
  (`content-sections.php`, deux champs `html`) — **texte à coller tel quel par Le Roi du
  Manioc, jamais rédigé dans le code** (`SiteContentSeeder` la seed vide ; la page
  affiche un message d'attente tant qu'elle l'est). Les deux liens `#` du pied de page
  (`site.footer.legal_notice`/`privacy`) pointent maintenant vers ces routes réelles.
- **Binding de modèle dans une action Livewire** : le paramètre typé est résolu par
  `getRouteKeyName()`. `Formation` utilise `slug` → dans les *actions* passer `int $id`
  + `findOrFail` (le binding par route param dans `mount()` reste OK, l'URL porte le slug).
- Français partout dans l'UI et les libellés.

## Sécurité / perf / sauvegardes (Phase 7)

- `SecurityHeaders` (append au groupe `web`) : nosniff, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, CSP modérée (`object-src 'none'`, `frame-ancestors 'self'`, `frame-src`
  limité à YouTube/Vimeo/Bunny ; pas de restriction `script-src` — Livewire/Alpine). HSTS si HTTPS.
  Les réponses `Content-Disposition` (preuves) sont exclues.
- `Password::defaults()` : 8+ caractères, lettres + chiffres ; `uncompromised()` en production.
- `/dashboard` = `DashboardController` invocable (plus de closure) → `route:cache` fonctionne.
- Journal d'audit : `Admin\ActivityLog` (`/admin/journal`), lit `spatie/activitylog`
  (Payment + User loggés).
- Sauvegardes : `php artisan backup:run` (mysqldump + zip de `storage/app/private`, garde 14) —
  planifié dans `routes/console.php` (`Schedule::`). `DB_DUMP_BINARY` si mysqldump hors PATH.
- **Média de leçon** (`config/media.php`) : la leçon a une `type` (video / document / quiz) et
  une source vidéo `video_provider` (`none` / `link` / `bunny` / `upload`).
  - `link` → YouTube/Vimeo auto-détecté ; `bunny` → embed signé/expirant si `services.bunny.token_key` ;
    `upload` → fichier sur le disque **privé** `local`, servi par `LessonMediaController@video`
    (route `lessons.video`, gardée par `FormationPolicy@follow`, `response()->file()` = Range/seek OK).
  - `LessonAttachment` : PDF/fiches jointes, disque privé, route `lessons.attachment`
    (`inline` pour les PDF). Une leçon `document` affiche son 1er PDF en `<iframe>`.
  - `Lesson::mediaKind()` = `embed | file | document | none` pilote l'affichage du lecteur.
  - Filigrane nom + téléphone masqué sur les vidéos `upload` des formations payantes.
  - ⚠️ Upload direct plafonné par `MEDIA_VIDEO_MAX_MB` + `upload_max_filesize` du serveur —
    pour de la vidéo à l'échelle : source `bunny` ou `MEDIA_VIDEO_DISK=s3`.
- **Langue de la vitrine (FR par défaut / EN au choix)** : bascule FR/EN — deux icônes drapeau
  (🇫🇷/🇬🇧, pas un simple toggle) dans l'en-tête + menu mobile de `public-layout.blade.php` et dans
  `layouts/guest.blade.php` (écrans de connexion/inscription) — `route('locale.switch', $locale)`,
  stockée dans un cookie `locale` (persistant, `Cookie::forever()`) appliqué par le middleware `App\Http\Middleware\SetLocale`
  (append sur le groupe `web`, cf. `bootstrap/app.php`). Seule l'**interface statique** est traduite :
  `lang/{fr,en}/site.php` (vitrine : menus, boutons, titres non éditoriaux, pied de page, page
  Contact) et `lang/{fr,en}/guest.php` (connexion/inscription/mot de passe oublié/vérification
  e-mail, monde CSS/JS `auth.{css,js}`) — le contenu tapé dans le CMS/les modèles (`SiteContent`,
  `Formation`, `MarketplaceListing`…) reste toujours en français, quel que soit le cookie. Pages
  encore 100 % françaises (non traduites) : back-office admin, espace apprenant, catalogue/
  boutique/marketplace publics dans `home.blade.php` (les cartes elles-mêmes viennent des modèles),
  formulaires d'inscription aux campagnes (`public/registration-form.blade.php`). Ne pas étendre
  cette traduction au contenu éditorial sans décision explicite (ça impliquerait de dupliquer les
  champs CMS FR/EN — chantier à part).

## Revue de clôture V1 — sécurité & QA (Phase 11)

Passe finale transverse sur les Phases 1-10 (§32 : couverture du périmètre V1 de bout en
bout ; §33 : vérifier qu'aucune fonctionnalité hors périmètre ne s'est glissée dans le
code). Rien de la liste du §33 (paiement en ligne réel, portefeuille électronique, escrow,
gestion de transporteurs, calcul d'itinéraire, application mobile native, commission
automatique, abonnement Premium, publicité automatisée) n'a été trouvé dans le code —
confirmé explicitement plutôt que supposé (audit par recherche exhaustive de mots-clés) :
les seuls faux positifs étaient `FormationAccess::Premium` (label « payant » vs
« gratuit » d'une formation, pas un abonnement récurrent à la plateforme) et le champ
`PaymentSetting::intl_link` (un simple lien externe optionnel vers un moyen de paiement
tiers configuré par l'admin, pas une intégration de gateway — cohérent avec « Le Roi du
Manioc ne touche pas l'argent »).

- **Audit des policies et du contournement admin** : chaque policy (`CropOfferPolicy`,
  `BuyerNeedPolicy`, `ProducerProfilePolicy`, `BuyerProfilePolicy`, `ConnectionRequestPolicy`,
  `CollaborationPolicy`, `ConversationPolicy`, `ReviewPolicy`, `FormationPolicy`) délègue
  bien à un prédicat du modèle (ownership ou `canXxxBy`) — aucune ne réimplémente sa propre
  logique. Aucune ne revérifie `isActive()` explicitement : c'est **volontairement**
  délégué au middleware `active` (routes + persistant Livewire), jamais dupliqué dans la
  couche policy — cohérence confirmée, pas un oubli.
  - **Asymétrie corrigée dans `Gate::before`** (`AppServiceProvider::boot()`) : l'exclusion
    du contournement admin sur les actions « une partie agit à la place d'une autre »
    couvrait déjà `Collaboration` (`declarePayment`/`confirmPayment`/`contestPayment`/
    `markDeliveryStep`/`cancel`) mais pas `ConnectionRequest` (`accept`/`cancel`/`refuse`/
    `moveToNegotiation`/`propose`/`confirmCollaboration`) — un admin passait donc la
    policy générique sur ces dernières (sans jamais pouvoir réellement muter l'état, le
    garde-fou du modèle `canXxxBy()` le bloquant quand même : aucune faille exploitable,
    mais la policy elle-même répondait faux). Ajout d'une deuxième liste
    `$connectionRequestPartyOnly` symétrique à `$collaborationPartyOnly` — les deux
    listes existent pour que la couche **policy seule** (`Gate::allows()` isolé, sans
    appeler la transition) réponde toujours correctement, pas seulement le modèle.
  - **`Admin\Collaborations::resolveDispute()`** appelait le modèle directement sans
    `$this->authorize('resolveDispute', ...)`, contrairement à toutes les autres actions
    de transition sur `Collaboration`/`ConnectionRequest` (qui passent systématiquement
    par `authorize()` avant d'appeler le modèle, même quand la garde du modèle suffirait
    seule) — corrigé pour rester cohérent avec cette convention établie.
  - **`Connect\Conversation`** (composant enfant, jamais routé directement aujourd'hui —
    imbriqué sous `Connect\Show`, déjà autorisé) n'avait aucune vérification propre :
    un tiers qui l'atteindrait quand même (si un jour exposé autrement) verrait le
    contenu des messages. Plutôt que bloquer `mount()` (ce qui aurait cassé le
    comportement volontaire déjà testé « un tiers peut ouvrir le composant, voir qu'il ne
    peut pas écrire, mais jamais envoyer » — cf. `MessagingTest`), le garde-fou est posé
    dans `render()` : `$messages` reste une collection vide pour quiconque n'est ni partie
    ni admin, alors que `canSend`/le formulaire restent gérés comme avant.
- **Audit du rate limiting** : les actions déjà couvertes (`CreateConnectionRequest`,
  `SendConversationMessage`, `DeclarePayment`, formulaires publics `contact.send`/
  `newsletter.store`/`inscription.store`) l'étaient déjà correctement. Gaps comblés,
  tous suivant le même idiome `RateLimiter::tooManyAttempts()`/`hit()` avec une clé
  `'action:'.$user->id` (même famille que l'existant) :
  - `Learner\ProducerProfile`/`BuyerProfile::save()` — aucune limite auparavant (ni
    `RateLimiter`, ni `throttle:` sur la route `/producteur`/`/acheteur`) ; 10 tentatives/h,
    couvre create ET édition puisque les deux passent par la même méthode.
  - `Producer\OfferForm`/`Buyer\NeedForm::save()` — la route `.create` avait déjà
    `throttle:6,1` (re-appliqué à chaque `wire:click` via
    `Livewire::addPersistentMiddleware`), mais **`.edit` n'avait rien** : ajout d'un
    `RateLimiter` directement dans `save()` (6/60s), qui couvre les deux routes
    uniformément plutôt que de dupliquer un `throttle:` sur `.edit`.
  - `Actions\SubmitReview` — aucune limite (la contrainte unique `(collaboration_id,
    rater_id)` limite déjà l'abus réel, mais rien n'empêchait un martèlement de
    soumissions invalides) ; 10 tentatives/h.
- **Audit de la CSP** (`SecurityHeaders`) : recherche exhaustive de toute ressource externe
  référencée dans `resources/` (polices, scripts, iframes) confrontée aux directives
  actuelles — **aucun écart trouvé**, tout hôte externe réellement utilisé
  (`fonts.googleapis.com`/`fonts.gstatic.com`, `youtube-nocookie.com`, `player.vimeo.com`,
  `iframe.mediadelivery.net`) est déjà couvert par la bonne directive. Rien à changer ; la
  CSP n'a donc pas été modifiée dans cette phase — confirmé par audit, pas supposé.
- **Densité de couverture des tests** : chaque transition d'état (`ConnectionRequest`,
  `Collaboration`, `Payment`, `Order`, `ProducerProfile::rejectVerification()`,
  `ContactMessage::markRead()`) a maintenant un test pour CHAQUE branche de refus
  identifiée (mauvais acteur, mauvais statut source, no-op sur un état déjà atteint,
  transition depuis un état terminal) — pas seulement le chemin heureux. Exemples ajoutés
  cette phase : `refuse()` testé depuis chacun des 3 statuts non-terminaux où il est
  autorisé (`acceptee`/`negociation` des deux côtés/`proposition`) ET depuis les 2 états
  terminaux où il ne l'est plus (`refusee`/`annulee`, en plus de `collaboration_confirmee`
  déjà couvert) ; `propose()`/`confirmCollaboration()` testés contre un tiers, pas
  seulement un mauvais statut ; `Collaboration::cancel()` testé contre un tiers ;
  `markDisputed()`/`Payment::reject()` testés pour leur propre idempotence (pas seulement
  celle de leur pendant `confirm()`/`resolveDispute()`) ; `Order::cancel()`/
  `markValidated()` testés sur leurs branches de garde (statut déjà terminal, mauvais
  statut source).

## Audit design — visibilité de la mise en relation (Phase 12)

Constat (audit communication/design) : la mise en relation producteurs↔acheteurs (§14,
« le cœur de la V1 ») n'avait **aucune** entrée dans la navigation principale (seulement
un lien noyé au pied de page parmi 8 autres), était traitée sur l'accueil comme un bloc
de texte générique sans photo, et les pages `/producteurs`/`/besoins` n'avaient aucun
hero (contrairement à Formations/Marketplace qui en ont un depuis la mise à niveau
design). Corrections, sans toucher à la palette/aux polices existantes :

- **Nav** : nouveau lien structurel « Producteurs & Acheteurs » (`lang/site.php` →
  `site.nav.connect`, pas le tableau positionnel `entete.menu` — même statut
  qu'Accueil/Contact) injecté entre Marketplace et Communauté dans
  `public-layout.blade.php`, avec un traitement visuel distinct (`.nav-highlight`,
  pastille dorée + icône) plutôt qu'un simple lien texte de plus, pour qu'il ne soit
  plus invisible. Pointe vers `producers.index` ; `aria-current` réagit aussi aux routes
  `needs.*`.
- **Accueil** : la section `#producteurs` (désormais classe `.connect`, pas
  `.section` générique) passe en fond sombre `--soil` + accent doré radial (même
  esprit que `.stats`), avec une vraie photo (nouveau champ CMS `image` sur
  `producteurs_section`, même mécanique que `formations_section`/`marketplace_section`)
  et un badge « Producteur vérifié » flottant sur la photo. L'indicateur communauté
  (§8.1) y a été déplacé (pas dupliqué) depuis `#communaute`.
- **Pages `/producteurs` et `/besoins`** : nouveau `.page-hero` (même gabarit que
  Formations/Marketplace), alimenté par la même section CMS `producteurs_section` et
  des chiffres calculés à la volée dans `Public\Producers`/`Public\Needs` (producteurs
  vérifiés, zones couvertes, offres/besoins actifs — jamais inventés). Les anciens
  `section-head` codés en dur (« Annuaire », etc.) ont été retirés au profit du hero.
  `.page-hero-badge` (nouveau, pastille verte flottante sur la photo) signale la
  confiance dès l'arrivée sur `/producteurs`, avant même la grille de cartes.

## Audit de conformité V1 — écarts comblés (Phase 13)

Audit croisé entre `Cahier_des_charges_Roi_de_Manioc_1.docx` (V1) et le code réel : le
périmètre §32 était déjà respecté presque intégralement (confirmé par recherche directe
dans le code, pas seulement par CLAUDE.md), à l'exception de 4 écrans/documents demandés
explicitement par le cahier mais jamais construits. Comblés ici :

- **Tableaux de bord producteur/acheteur** (§9/§11) : `App\Livewire\Producer\Dashboard`
  (`/mon-espace/producteur/tableau-de-bord`) et `App\Livewire\Buyer\Dashboard`
  (`/mon-espace/acheteur/tableau-de-bord`) — jusqu'ici, ces métriques existaient déjà en
  base mais éparpillées entre `Offers`/`Needs`/`Requests` sans écran de synthèse. Chacun
  agrège en tuiles `<x-adm.stat>` (réutilisé tel quel, même monde CSS `admin.css` que le
  layout learner) exactement les champs demandés par le cahier : produits publiés/
  demandes reçues/collaborations en cours et terminées/note moyenne/notifications côté
  producteur ; besoins publiés/demandes reçues/collaborations/favoris/notifications côté
  acheteur — plus un tableau des 5 dernières demandes (historique). Devient le premier
  lien de « Mon royaume » une fois le profil activé (`components/layouts/learner.blade.php`).
- **Favoris** (§11) : table pivot `favorites` (`user_id`, `producer_profile_id`, unique) —
  pas de modèle Eloquent dédié, consommée via `User::favoriteProducers()`
  (`belongsToMany` + `toggle()`/`detach()` natifs, aucune logique à réinventer). Ouvert à
  **tout utilisateur connecté** (pas seulement un `buyer_profiles` déjà créé) — favoriter
  en parcourant `/producteurs` peut précéder l'activation de l'espace acheteur.
  `FavoriteController` (invocable, `POST /producteurs/{producerProfile}/favori`,
  middleware `auth`) plutôt qu'une action Livewire : un visiteur non connecté qui clique
  le cœur profite gratuitement du guest→connexion→retour standard de Laravel sur une
  vraie navigation — un formulaire (pas un lien) pour rester en POST, jamais de mutation
  sur une requête GET. Bouton cœur flottant sur chaque carte de `/producteurs`
  (`.fav-btn`, `resources/css/vitrine.css`), état reflété via `favoriteIds` (une seule
  requête pour toute la page). Liste dédiée `App\Livewire\Buyer\Favorites`
  (`/mon-espace/acheteur/favoris`, non paginée — même logique que Formations/Boutique/
  Événements, un utilisateur en a toujours un nombre borné).
- **Modération des avis côté admin** (§25 — « Marketplace : évaluations », jusque-là
  seulement un compteur en lecture seule sur `Admin\ProducerShow`) : `App\Livewire\
  Admin\Reviews` (`/admin/avis`). Ne permet **jamais** d'éditer/supprimer le contenu d'un
  avis (`Review` reste immuable après soumission, cf. Phase 8/Sécurité) — seulement de le
  **masquer** de l'affichage public en cas d'abus (`Review::hide()`/`unhide()`, colonnes
  `hidden_at`/`hidden_by`/`hidden_reason`, jamais dans `$fillable`, `forceFill` uniquement
  — même patron que `ProducerProfile::verify()`/`rejectVerification()`, idempotent,
  motif obligatoire et journalisé via `LogsActivity`). Pas de policy dédiée : protégé
  comme le reste par le seul middleware `admin`, même choix que `ProducerVerification`.
  `ProducerProfile::receivedReviews()` exclut désormais `whereNull('hidden_at')` — un
  seul endroit filtré, dont bénéficient automatiquement `averageRating()`/
  `reviewsCount()` ET les `withAvg`/`withCount` publics de `Public\Producers`, sans
  toucher à ces appelants.
- **Documents juridiques** (§44 — le cahier en demande 6 : CGU, confidentialité, règles
  de publication, règles de comportement, gestion des litiges, politique de paiement ;
  seuls 2 existaient). Choix éditorial délibéré : regroupés en **un seul** nouveau
  document plutôt que 4 pages distinctes — ces 4 sujets se lisent naturellement comme les
  chapitres d'une même charte, et 6 liens de pied de page auraient nui à la lisibilité du
  footer pour un gain nul. Nouveau champ CMS `legal.charte_utilisation` (même mécanique
  HTML libre que les 2 champs existants), route `/charte-d-utilisation`
  (`legal.charter`), `PageController::usageCharter()` — réutilise le même gabarit
  générique `pages/legal.blade.php` (message d'attente tant que le champ est vide). Lien
  ajouté au pied de page public à la suite des deux liens légaux existants.

Tests : `tests/Feature/Producer/DashboardTest.php`, `tests/Feature/Buyer/DashboardTest.php`,
`tests/Feature/Buyer/FavoritesTest.php` (bascule, garde-fous, reflet sur le catalogue
public, propriété — un utilisateur ne voit que ses propres favoris),
`tests/Feature/Admin/ReviewsTest.php` (accès réservé à l'admin, masquage/démasquage,
motif obligatoire, idempotence, contenu jamais altéré, exclusion de la moyenne/du
catalogue public une fois masqué), `tests/Feature/PublicPagesTest.php` (étendu pour la
charte d'utilisation — mêmes 3 tests que les 2 pages légales existantes).

- **Correctif UX — un admin en supervision ne doit jamais ressembler à un client** :
  `Connect\Show`/`Collaboration\Show` (`/mon-espace/demandes/{id}`,
  `/mon-espace/collaborations/{id}`) sont des écrans **partagés**, ouvrables par un admin
  en lecture seule (Phase 10, §37) — mais ils tournent sous `components.layouts.learner`,
  dont le menu était calculé uniquement à partir de `isProducer()`/`isBuyer()` de
  l'utilisateur courant. Un admin (qui n'a ni l'un ni l'autre) voyait donc le label
  « Apprenant », les accroches « Devenir producteur »/« Devenir acheteur » et aucun moyen
  de revenir à l'administration — exactement comme n'importe quel client, signalé en
  usage réel. Corrigé sans toucher au menu réel des vrais apprenants :
  - `components/layouts/learner.blade.php` : `$isAdmin` masque les accroches
    « Devenir... » (un groupe « Mon royaume » qui deviendrait vide est retiré du menu,
    `array_filter`), le label devient « Administrateur » / le tag de marque « Vue
    administrateur », et un lien « ← Retour à l'administration » (`admin.dashboard`)
    s'ajoute au pied de la barre latérale.
  - `Connect\Show`/`Collaboration\Show::render()` exposent chacun `isViewerAdmin`
    (`$user->isAdmin() && ! $model->isParty($user)`, même calcul que celui déjà utilisé
    par `Collaboration\Show` pour le bouton « Signaler un litige ») — les deux vues
    affichent désormais un bandeau dédié (« Vue administrateur — lecture seule ») avec un
    lien de retour vers l'écran de supervision correspondant (`admin.connection-requests`/
    `admin.collaborations`), plutôt que de laisser l'admin deviner qu'il est en train de
    consulter un écran client. Testé dans `ConnectConnectionRequestsTest`/
    `ConnectCollaborationsTest` (`test_the_admin_sees_a_read_only_banner_and_no_customer_upsell`).

## Négociation façon messagerie instantanée (Phase 14)

Deux évolutions demandées ensemble : l'acheteur doit voir des offres producteur dès sa
connexion (§11), et la messagerie d'une demande doit se sentir comme une vraie discussion
(façon WhatsApp) avec de vraies propositions dedans, pas un simple bouton générique
« Faire une proposition » déconnecté du fil.

- **Offres sur le tableau de bord acheteur** : `Buyer\Dashboard` ajoute `recentOffers`
  (6 dernières `CropOffer::published()`, avec le producteur), affichées en cartes avec un
  bouton « Contacter » direct — un raccourci vers `/producteurs` reste disponible pour
  filtrer plus finement. Pas de logique nouvelle, juste la même requête que le catalogue
  public, réutilisée à l'endroit où l'acheteur atterrit déjà.
- **Messagerie en plein cadre** : `Connect\Show`/`connect/show.blade.php` sont restructurés
  en deux zones (`.connect-shell` > `.connect-layout`) — le chat (`.chat-panel`, flex:1)
  prend toute la hauteur disponible (`calc(100vh - 210px)`, defilement interne, zone de
  saisie fixée en bas) et un panneau latéral (`.connect-side`, largeur fixe, scroll
  indépendant) regroupe Progression/Détails/Historique. Empilé verticalement sous 900px
  (`.chat-panel{height:560px}` fixe sur mobile, pas de hauteur pilotée par le viewport qui
  n'aurait pas de sens en dessous du clavier virtuel).
- **Propositions structurées dans le chat, pas un bouton à part** : le bouton générique
  « Faire une proposition » du panneau Progression a été retiré (c'était le seul appelant
  de `Connect\Show::propose()`, supprimé) — remplacé par un composeur dans
  `Connect\Conversation` (`startProposal`/`sendProposal`/`cancelProposal`, formulaire
  quantité/unité/prix/note) qui envoie une **carte de proposition** dédiée dans le fil
  (`ConversationMessage.type = 'proposition'`, `proposal_terms` JSON — `type` reste une
  colonne string simple plutôt qu'un enum PHP dédié, même raisonnement que
  `BuyerNeed.frequency`). La transition de statut sous-jacente (`negociation` →
  `proposition`) est **exactement** celle qui existait déjà (`ConnectionRequest::propose()`,
  ability `propose` de `ConnectionRequestPolicy`) — `App\Actions\SendConnectionProposal`
  (nouvelle action, même famille que `SendConversationMessage`/`CreateConnectionRequest`)
  ne fait qu'y accoler le message enrichi, sans dupliquer la garde. La note facultative qui
  accompagne une proposition reste soumise à `ContactDetector` comme n'importe quel message
  (§16) — seuls les champs structurés (quantité/unité/prix) échappent au scan, puisque ce
  sont des nombres/énumérations, jamais du texte libre.
  - **Accepter/Refuser directement sur la carte** : tant que le statut reste `proposition`,
    la DERNIÈRE carte de proposition du fil affiche des boutons Accepter/Refuser — mêmes
    abilities que le panneau latéral (`confirmCollaboration`/`refuse`), donc aucune règle
    d'autorisation dupliquée. Comme la machine à états ne permet pas de reproposer depuis
    `proposition` (seuls confirm/refuse en sortent, cf. `ConnectionRequestStatus`), il ne
    peut jamais exister plus d'une carte « active » à la fois — pas besoin d'un statut
    propre sur le message, la dernière carte de type `proposition` EST la carte en cours
    tant que `connectionRequest.status === proposition`.
  - **Composant enfant → parent, sans dupliquer la logique métier** : `Connect\Conversation`
    (enfant) appelle directement les mêmes transitions de modèle que `Connect\Show`
    (parent) pour Accepter/Refuser — les deux ne sont que de fins appels
    `authorize() + transition()`, aucune règle propre à l'un ou l'autre. Après une action
    dans le chat, l'enfant dispatch un événement `connection-request-updated` que le parent
    écoute (`#[On(...)]`) pour rafraîchir SA PROPRE instance du modèle (stepper, pastille de
    statut) — sans ça, le panneau latéral resterait figé sur l'ancien statut après une
    action faite depuis le chat.
  - **Refactor associé — avec un changement de comportement réel, corrigeant un bug** : la
    création de la `Collaboration` (+ sa `CollaborationDelivery`) à la confirmation vivait
    auparavant dans une méthode privée de `Connect\Show` (`createCollaboration()`) —
    déplacée dans `ConnectionRequest::confirmCollaboration()`/`createCollaborationAgreement()`
    lui-même (toujours idempotent via `firstOrCreate`, toujours dans une transaction) pour
    que N'IMPORTE QUEL appelant (l'écran principal, la carte de proposition dans le chat, un
    futur point d'entrée) obtienne le même comportement automatique sans dupliquer cette
    création. **Correction ⚠️ (audit Phase 20)** : contrairement à ce qu'affirmait cette
    section avant l'audit, ce n'est PAS un changement neutre — le docblock de
    `createCollaborationAgreement()` explique lui-même que `agreed_quantity`/`unit`/
    `agreed_price_total` sont désormais repris de la DERNIÈRE proposition structurée quand
    il y en a une, plutôt que systématiquement de l'offre/besoin d'origine comme avant («
    constat en usage réel : `agreed_price_total` restait toujours `null` et la quantité
    n'était jamais celle négociée »). Les tests existants restaient verts simplement parce
    qu'aucun ne couvrait un scénario avec proposition chiffrée avant confirmation — pas
    parce que la sortie était réellement identique. Voir aussi la note mise à jour sur
    `Collaboration::estimatedValue()` plus haut.

Tests : `tests/Feature/Connect/ProposalTest.php` (carte créée + statut avancé, garde de
statut/acteur via l'ability `propose` existante, note toujours scannée/masquée §16,
acceptation depuis le chat crée bien la collaboration, refus depuis le chat clôt la
demande, le panneau parent se resynchronise après une action de l'enfant, l'ancien bouton
générique a disparu, rendu de la page complète en HTTP réel avec la nouvelle mise en
page, erreur de validation du prix affichée sous le bon champ — bug de correspondance de
noms détecté à l'écriture des tests, corrigé avant livraison). `tests/Feature/Buyer/
DashboardTest.php` étendu (offres publiées visibles, brouillons exclus, lien de contact
correct).

- **Bug UX signalé en usage réel — « on dirait qu'il y a deux tableaux de bord »** : un
  compte cumulant les deux espaces (producteur ET acheteur — cumul explicitement supporté,
  cf. § Espaces producteur/acheteur) voyait dans « Mon royaume » deux liens de menu
  strictement identiques (« Tableau de bord », sans plus de précision). Un premier
  correctif avait juste renommé les deux libellés (« Tableau de bord producteur »/
  « ... acheteur ») — mais l'utilisateur a demandé mieux : un tableau de bord **unique**
  plutôt que deux pages distinctes juste mieux nommées. Voir Phase 15 ci-dessous pour la
  vraie fusion qui a suivi (les deux `Livewire\Producer\Dashboard`/`Buyer\Dashboard`
  créés en Phase 13 ont été supprimés au profit d'un seul écran).

## Commandes structurées producteur↔acheteur — CropOrder (§10)

**Absente de ce document jusqu'à l'audit qui l'a ajoutée** (les migrations datent du
2026-09-28/29, juste après la Phase 14 ci-dessus) : un parcours de commande structuré
façon « bouton Commander », **délibérément séparé** du système de mise en relation par
chat (`ConnectionRequest`/`Conversation`/`Collaboration`) — pas de discussion libre ici,
un objet métier à statuts explicites (`App\Enums\CropOrderStatus`, 13 états).

- **Flux** : l'acheteur passe commande sur une offre (`App\Livewire\Buyer\CropOrderForm`,
  action `App\Actions\CreateCropOrder`) → le producteur accepte/refuse → le producteur
  soumet en privé (admin + producteur uniquement, jamais visible de l'acheteur) un prix
  total et des frais de livraison proposés → **l'admin valide ou renvoie** ces conditions
  (`CropOrder::approveDeliveryConditions()`/`rejectDeliveryConditions()`, pouvoir admin
  exclusif) → une fois validées, acheteur et producteur négocient les frais de livraison
  par contre-propositions successives (`crop_order_delivery_proposals`, historique
  append-only, jamais écrasé — `App\Enums\DeliveryProposalStatus`) → acceptation → commande
  `confirmee` avec un total figé (`total_amount`) → puis soit une **« Aide livraison »**
  suivie par l'admin (`App\Models\DeliveryAssist`, 6 statuts, checklist manuelle —
  *aucun* transporteur réel, aucune géolocalisation/calcul d'itinéraire, juste un suivi
  administratif : les colonnes `courier_name`/`courier_contact` existent en base mais ne
  sont écrites/affichées nulle part, vestige inerte à ne pas réactiver sans un vrai besoin),
  soit une **livraison auto-organisée** déclarée directement par l'une des parties sans
  admin (`declareSelfArrangedDelivery`/`confirmSelfArrangedDelivery`/
  `cancelSelfArrangedDelivery`).
- **Ne contredit pas le périmètre hors-scope du §33** (audité explicitement : pas de
  gestion de transporteurs, pas de calcul d'itinéraire) — c'est un tracker de
  checkout/exécution manuel, pas une intégration logistique.
- **Même doctrine de garde-fous que Collaboration/ConnectionRequest** : chaque transition
  a son `canXxxBy()`, `CropOrderPolicy` délègue 1:1, et `AppServiceProvider::boot()`
  exclut les actions « une partie agit » (`$cropOrderPartyOnly`) du contournement admin
  générique — seuls `approveDeliveryConditions`/`rejectDeliveryConditions`/
  `markDeliveryAssistStep` restent des pouvoirs admin propres.
- **Supervision admin** : `Admin\CropOrders` (`/admin/commandes-produits`, lecture seule +
  validation des conditions) et `Admin\DeliveryAssists` (`/admin/aide-livraison`, la
  checklist). Nav sous le groupe « Mise en relation ».
- **Bug corrigé (audit)** : `requestDeliveryAssistance()` utilisait `firstOrCreate([], ...)`
  sur une relation 1-ligne-par-commande — si une aide avait déjà été annulée
  (`markDeliveryAssistStep(..., Annulee)`) puis redemandée, `firstOrCreate` réutilisait la
  ligne figée à `annulee` au lieu d'en réinitialiser une nouvelle, et aucune transition ne
  permettant de sortir de `annulee`, la commande restait bloquée dans `aide_livraison`
  **définitivement** (et disparaissait du filtre admin par défaut, qui exclut
  `annulee`/`livree`). Remplacé par `updateOrCreate([], [...])` qui remet explicitement
  `status`/`requested_at` à `demande_aide` et vide `delivered_at`/`cancelled_at`.

Tests : `tests/Feature/CropOrder/{CreateCropOrderTest,CropOrderFormAndListsTest,
CropOrderNotificationsTest,CropOrderShowTest,CropOrderTransitionsTest}.php`,
`tests/Feature/Admin/{AdminCropOrdersTest,AdminDeliveryAssistsTest}.php`.

## Tableau de bord UNIQUE par compte (Phase 15)

Suite directe de la Phase 13 (voir juste au-dessus) : renommer les deux libellés ne
suffisait pas — l'utilisateur voulait UN SEUL tableau de bord par compte, avec les
informations de chaque rôle actif réunies dessus, jamais des pages séparées. Décision
symétrique pour les trois profils qui peuvent coexister sur un même compte (apprenant/
producteur/acheteur) : plus un utilisateur cumule de rôles, plus la page affiche de
sections — jamais plus de liens de menu.

- **`App\Livewire\Producer\Dashboard` et `App\Livewire\Buyer\Dashboard` (Phase 13) sont
  supprimés** (classes + vues) — leurs requêtes ont été rapatriées directement dans
  `App\Livewire\Learner\Dashboard::render()`, préfixées `producer*`/`buyer*` pour éviter
  toute collision de clés dans le tableau passé à la vue. Les routes `learner.producer.
  dashboard`/`learner.buyer.dashboard` sont retirées de `routes/web.php` (plus aucune page
  à cette adresse).
- **Une seule page, des sections conditionnelles** : `resources/views/livewire/learner/
  dashboard.blade.php` affiche toujours la progression des formations, PUIS — si
  `$isProducer` — un bloc « Espace producteur » (mêmes tuiles `<x-adm.stat>` que l'ancien
  écran dédié : produits publiés/demandes reçues/collaborations/note moyenne), PUIS — si
  `$isBuyer` — un bloc « Espace acheteur » (besoins/demandes/collaborations/favoris + les
  3 dernières offres producteur publiées, avec bouton Contacter). Un compte cumulant les
  deux voit les DEUX blocs à la suite sur la même page ; un apprenant pur ne voit ni l'un
  ni l'autre (juste ses formations + le bloc d'accroche « Devenir producteur/acheteur »
  existant, inchangé).
- **Nav simplifiée** : le lien « Mon espace » (en tête de « Ma formation ») est renommé
  « Tableau de bord » tout court — puisque c'est désormais VRAIMENT le seul tableau de
  bord de tout le compte. Les groupes « Mon royaume » (producteur/acheteur) perdent leur
  lien « Tableau de bord » respectif ; il ne reste que les pages d'action (Profil, Mes
  offres/besoins, Mes demandes, Mes favoris).
- **Admin non touché, par cohérence plutôt que par nécessité** : `/admin` (« Vue
  d'ensemble ») était déjà un tableau de bord unique — aucune duplication analogue n'y
  existait (§37 tient `admin.dashboard`/`admin.connect-stats` volontairement distincts par
  leur objet, pas par accident, cf. Phase 10). Rien à fusionner de ce côté.

Tests : `tests/Feature/Learner/DashboardTest.php` (nouveau, remplace les anciens
`Producer\DashboardTest`/`Buyer\DashboardTest` supprimés) — section producteur/acheteur
affichée seulement quand applicable, propriété (un producteur ne voit que ses propres
offres), les deux sections apparaissent ensemble pour un compte cumulant les deux rôles,
absence totale des anciens libellés qualifiés, un seul élément de nav actif à la fois, et
confirmation explicite que les deux anciennes routes n'existent plus
(`Route::has(...)` false). `DashboardProducerBuyerCtaTest` (bloc d'accroche) inchangé et
toujours vert.

## Tableau de bord UNIQUE côté admin aussi (Phase 16)

Demande directe de suite à la Phase 15 : « je veux un tableau de bord unique chez admin
aussi ». `/admin` (« Vue d'ensemble ») et `/admin/mise-en-relation` (« Chiffres de la mise
en relation ») étaient déjà deux écrans à LIBELLÉS distincts (pas le bug exact de la
Phase 15), mais restaient malgré tout deux destinations candidates au rôle de « vue
d'ensemble » de l'admin — même défaut de fond, cohérence oblige.

- **`App\Livewire\Admin\ConnectStats` (Phase 10) est supprimé** (classe + vue + route
  `admin.connect-stats` + lien de nav « Chiffres de la mise en relation ») — ses 8
  agrégats (producteurs/acheteurs/offres/besoins/demandes/collaborations/litiges/volume
  estimé, mêmes règles qu'avant, y compris l'exclusion des collaborations annulées du
  volume) sont rapatriés tels quels dans `App\Livewire\Admin\Dashboard::render()`, affichés
  comme une section « Mise en relation » supplémentaire tout en bas de `/admin`, après les
  paiements à vérifier.
- **Non touché, volontairement** : les autres écrans de supervision (`admin.producers`,
  `admin.buyers`, `admin.crop-offers`, `admin.buyer-needs`, `admin.connection-requests`,
  `admin.collaborations`, `admin.reviews`) restent des pages séparées — ce ne sont PAS des
  « vues d'ensemble » concurrentes, ce sont des écrans d'action/liste avec leur propre
  objet (gérer, filtrer, agir), la même distinction que Paiements/Commandes/Messages n'ont
  jamais été candidats à fusionner dans `/admin` non plus. Seuls les écrans purement
  agrégats (sans liste ni action) sont concernés par ce principe.

Tests : `tests/Feature/Admin/DashboardTest.php` (nouveau, remplace `ConnectStatsTest`
supprimé) — mêmes scénarios (compteurs corrects, volume excluant les annulées, accès
réservé à l'admin) rejoués contre le composant fusionné, plus deux vérifications propres à
la fusion : la route `admin.connect-stats` n'existe plus (`Route::has()` false) et le menu
n'affiche plus qu'un seul lien de navigation actif à la fois.

## Message reçu mis en avant sur le tableau de bord (Phase 17)

Demande directe : « il faut que le message envoyé par acheteurs ou producteurs attire
l'attention une fois connecté dans son tableau de bord » — la petite cloche du topbar
(`NotificationBell`, Phase 9) existait déjà mais restait trop discrète pour un événement
aussi actionnable qu'un nouveau message de négociation.

- **Bannière dédiée, tout en haut de `/mon-espace`** — avant même les formations, pour
  qu'elle soit la toute première chose vue à la connexion. N'apparaît QUE pour les
  notifications de type `ConversationMessageSentNotification` (jamais pour les 7 autres
  types du §24 — demande acceptée, paiement déclaré, etc. — qui restent couvertes par la
  cloche existante, cf. le rappel discret ajouté juste en dessous : « N autre(s)
  notification(s)… — voir la cloche »). Chaque ligne est cliquable (marque lu + ouvre la
  demande, `wire:navigate`), plus un « Tout marquer comme lu » qui ne touche QUE les
  notifications de message (`markAllMessagesRead()`, `where('type', ConversationMessage
  SentNotification::class)` — jamais les autres types, sans quoi une notification "demande
  acceptée" encore utile disparaîtrait de la cloche sans avoir été vue).
- **Le corps du message n'apparaît toujours pas** dans la bannière (texte générique
  « X vous a envoyé un message à propos de « Y » » — cf. `ConversationMessageSentNotification`,
  décision Phase 9 inchangée) : seule sa PRÉSENCE doit attirer l'attention, pas son
  contenu, cohérent avec le fait que la coordonnée éventuelle d'un message reste de toute
  façon masquée à l'affichage (§16).
- `Learner\Dashboard::markMessageRead()`/`markAllMessagesRead()` — mêmes méthodes que
  `NotificationBell`, dupliquées ici à dessein plutôt que factorisées : ce sont de purs
  appels `notifications()->whereKey(...)->markAsRead()`/`update(['read_at' => now()])`
  sans logique propre, et les deux composants n'ont pas de relation parent/enfant qui
  permettrait de partager une méthode sans complexifier l'un des deux pour l'autre.

Tests : `tests/Feature/Learner/DashboardTest.php` étendu — un nouveau message se voit sur
le tableau de bord du destinataire (jamais chez l'expéditeur), marquer un message lu (ou
tout marquer) le retire de la bannière sans toucher aux autres types de notification, et
les notifications hors message ne déclenchent jamais cette bannière (seulement le rappel
discret).

## Le message initial doit apparaître dans le fil de discussion (Phase 18)

Signalé en usage réel avec une capture d'écran : une demande passée à « Acceptée »
affichait un fil de discussion vide (« Aucun message pour l'instant »), alors que
l'acheteur avait pourtant écrit un message en amorçant la demande — « je vois même pas le
message de l'acheteur, comment acceptée alors ». Cause : le texte tapé au moment de
contacter une offre/répondre à un besoin (`message_initial`, capturé par `Connect\
RequestOffer`/`RequestNeed`) n'a jamais été qu'une colonne sur `connection_requests` — il
n'a jamais transité par le système de messagerie (`conversations`/`conversation_messages`)
lui-même. Il n'existait qu'à un seul endroit : le paragraphe « Message initial » du
panneau « Détails », que l'admin/apprenant n'associe pas mentalement au chat.

- **`CreateConnectionRequest::handle()`** poste désormais ce texte comme la toute première
  `ConversationMessage` du fil, juste après avoir créé la demande — en passant par
  `SendConversationMessage` (même détection de coordonnées §16, même validation) plutôt
  que de l'insérer à la main, pour qu'il se comporte à tous égards comme un message
  normal. Un échec (rate limit de messagerie atteint pile à ce moment) est avalé
  silencieusement : `message_initial` reste de toute façon stocké sur la demande elle-même
  (et sert toujours de `terms_note` à la Collaboration une fois confirmée, cf. §18) — la
  création de la demande, l'action principale, ne doit jamais échouer pour ça.
- **`SendConversationMessage::handle()` gagne un paramètre `notify: bool = true`** — pour
  ce cas précis, `notify: false` : « nouvelle demande » (déjà déclenchée par
  `ConnectionRequestCreated`) reste LA notification de cette action ; émettre en plus
  « nouveau message » pour le même geste aurait doublé la notification pour la même action
  et contredit le §24 (« sans sur-notifier ») — confirmé par 4 tests déjà existants
  (`NotificationsTest`) qui comptaient précisément 1 notification à ce stade et ont
  immédiatement viré au rouge le temps de ce garde-fou.
- **Le paragraphe « Message initial » du panneau « Détails »** (`connect/show.blade.php`)
  est retiré — il ferait doublon avec la première bulle du fil, qui porte désormais
  exactement le même texte.

Tests : `tests/Feature/Connect/ConnectionRequestTest.php` étendu — le message initial
devient la première (et seule) `ConversationMessage` du fil ; aucune conversation n'est
créée quand le champ message est laissé vide (toujours facultatif) ; aucune notification
dupliquée ; le texte reste soumis à `ContactDetector` comme n'importe quel message.

## Audit de logique transverse (Phase 20)

Tout le travail des Phases 2 à 18 (+ CropOrder ci-dessus) existait uniquement dans le
répertoire de travail, jamais commité (seuls « Phase 1 » et « docs: CLAUDE.md » étaient
sur `master`) — **committé en bloc à cette phase** avant tout correctif, comme filet de
sécurité. Audit ciblé (4 passes : machine à états mise en relation/paiement, sécurité
comptes/rôles, CropOrder, CMS/i18n/catalogues publics) contre le code réel, 657 tests
verts avant et après. Correctifs appliqués :

- **`App\Support\PhoneNumber::normalize()` ne retirait pas le préfixe `00225`** (seul
  `225`/`+225` l'était) — un numéro saisi en `00225 07...` échappait à la déduplication et
  rendait le compte injoignable en connexion sous tout autre format. Corrigé (vérifie
  `00225` avant `225`) ; nouveau `tests/Unit/PhoneNumberTest.php`.
- **Blocage définitif d'une CropOrder** en cas de nouvelle demande d'aide-livraison après
  annulation — voir § CropOrder ci-dessus pour le détail.
- **Comptes suspendus gardant l'accès à du contenu sensible en session déjà ouverte** :
  `producers.favorite.toggle`, `payments.proof`, `lessons.video`/`lessons.attachment`
  n'étaient gardées que par `auth`, jamais `active` — un compte suspendu qui ne visitait
  aucune route `active` gardait donc le streaming vidéo/téléchargement de preuve/favoris
  indéfiniment. Les trois routes portent désormais `['auth', 'active']`.
- **Race condition (TOCTOU) sur l'anti-verrouillage admin** : `Admin\Members::changeRole/
  toggleSuspend/deleteMember` lisaient `isLastActiveAdmin()` puis écrivaient sans
  transaction ni verrou — deux suspensions croisées quasi simultanées entre les 2 seuls
  admins actifs pouvaient chacune passer le garde-fou et aboutir à zéro admin actif.
  `User::isLastActiveAdmin(bool $lockForUpdate = false)` accepte désormais un verrou
  (`lockForUpdate()` sur TOUS les admins actifs, soi-même inclus — verrouiller seulement
  « les autres » ne sérialiserait jamais deux transactions ciblant deux comptes
  différents), posé par les trois méthodes de `Admin\Members` dans un `DB::transaction()`
  qui verrouille aussi la ligne cible (`User::query()->lockForUpdate()->findOrFail(...)`).
  `Admin\Buyers::toggleSuspend()` réutilise sciemment le même garde-fou non verrouillé
  (défense en profondeur sur un compte qui n'est jamais admin en pratique) — laissé tel
  quel, aucun risque réel.
- **`role`/`status` de `User` retirés de `$fillable`** — ils y figuraient malgré la
  convention documentée plus haut (« ne viennent JAMAIS d'une requête utilisateur »), sans
  qu'aucun site d'appel actuel n'exploite la faille, mais sans aucun garde-fou structurel
  contre un futur `fill($request->all())`/`create($request->validated())` qui les
  inclurait par erreur — élévation de privilège instantanée le jour où ça arrive. Les deux
  seuls sites légitimes (`Admin\Members::createMember`, déjà en `forceFill` ; et
  `Auth\GoogleController::resolveUser`, converti de `User::create()` à `forceFill()` à
  cette phase) n'en dépendaient pas.
- **Un admin pouvait confirmer/rejeter son propre paiement** (`Payment::confirm()`/
  `reject()` ne vérifiaient que le statut `a_verifier`, jamais que l'admin n'était pas
  aussi le client) — aucun garde-fou structurel n'empêchait un compte admin, s'il devenait
  aussi client de la plateforme, de s'auto-valider. Nouveau `Payment::canBeDecidedBy()`
  (même idiome `canXxxBy` que Collaboration/ConnectionRequest) : refuse si
  `$payment->user_id === $admin->id`, idempotent comme le reste.
- **Documentation obsolète corrigée** : la Phase 14 affirmait à tort qu'aucun changement
  de comportement n'accompagnait le déplacement de la création de `Collaboration` dans le
  modèle (faux — voir la note ajoutée à cette section) ; le docblock de
  `CollaborationStatus` affirmait à tort que `litige` pouvait être atteint automatiquement
  depuis `contestPayment()` (faux — `contestPayment()` ramène toujours à `en_cours`) ;
  fil d'ariane admin (`components/layouts/admin.blade.php`) complété pour
  `admin.crop-orders`/`admin.delivery-assists`, oubliés lors de leur ajout.
- **Repéré, non corrigé (risque jugé négligeable ou hors périmètre d'un audit de
  logique)** : `database/migrations/..._add_evenements_section_to_site_contents.php::down()`
  invalide le cache `SiteContent` via un `Model::where(...)->delete()` plutôt qu'une
  instance (contourne l'invalidation par events) — un rollback de migration est rare en
  production, laissé tel quel plutôt que de complexifier une migration ponctuelle déjà
  appliquée ; le cookie de langue (`Cookie::forever('locale', ...)`) dure en réalité ~5 ans,
  pas « 1 an » comme décrit plus haut — cosmétique, `Cookie::forever()` reste le bon choix
  fonctionnel.
- **`Testimonial`/`Award::localized()` — corrigé après coup** : les deux modèles
  dupliquaient (au lieu de partager) une logique de repli FR/EN identique, byte pour byte,
  à celle de `SiteContent::localizeSection()` — pas fusionnée avec elle car forme de
  stockage différente (paire de colonnes `$field`/`${field}_en` ici, valeur bilingue brute
  `{"fr":...,"en":...}` là-bas), mais la duplication ENTRE `Testimonial` et `Award`
  eux-mêmes n'avait aucune raison d'exister. Extraite dans
  `App\Models\Concerns\HasLocalizedFallback` (trait), utilisé par les deux modèles — un
  seul endroit à faire évoluer désormais. `tests/Unit/HasLocalizedFallbackTest.php`
  (nouveau, aucun test ne couvrait `localized()` avant) verrouille le comportement des deux
  modèles à travers le trait.

## Gotcha environnement

`NODE_ENV=production` peut être défini globalement → `npm install` saute les devDependencies.
Toujours : `NODE_ENV=development npm install --include=dev` puis `npm run build`.

Socialite / phpseclib ne s'installent pas ici (l'antivirus Windows verrouille l'extraction).
La connexion Google est donc implémentée à la main (`App\Services\GoogleOAuth` + `Http::`).

## Tests

`php artisan test` — 560 verts. Vitrine + contact : `HomePageTest`, `ContactFormTest`
(dont carte de contact pilotée par le CMS — affichée/masquée selon `map_lat`/`map_lng`).
États vides + page 404 des pages publiques : `tests/Feature/PublicEmptyStatesTest.php`
(délibérément séparé de `PublicPagesTest` — base non seedée par `DemoSeeder`, pour ne pas
avoir à supprimer des formations/offres/témoignages après coup).
Back-office : `tests/Feature/Admin/`. Espace apprenant : `tests/Feature/Learner/`.
Paiement / anti-fraude : `tests/Feature/Payment/`. Comptes : `tests/Feature/Account/`,
`tests/Feature/Admin/MembersTest.php`, `tests/Feature/Auth/GoogleLoginTest.php`. Connexion
e-mail/téléphone : `tests/Feature/Auth/AuthenticationTest.php`, `tests/Feature/Auth/RegistrationTest.php`.
Durcissement : `tests/Feature/HardeningTest.php`. Média de leçon :
`tests/Feature/Admin/LessonMediaTest.php` (lien YouTube, upload privé gardé, PDF joint gardé,
suppression → fichiers effacés, leçon document). Formulaires d'inscription :
`RegistrationFormTest` (page publique, honeypot, notification e-mail, compte à rebours « premiers
inscrits », option de réservation à acompte réduit), `tests/Feature/Admin/RegistrationFormsTest.php`
(liste), `tests/Feature/Admin/RegistrationFormBuilderTest.php` (page dédiée, éditeur de points,
aperçu, échéance/acompte), `tests/Feature/Admin/RegistrationLeadsTest.php`. CMS :
`tests/Feature/Admin/ContentManagerTest.php` (invalidation du cache à l'enregistrement, upload/
suppression d'image, témoignages, repli FR/EN du contenu bilingue). Langue de la vitrine :
`tests/Feature/LocaleSwitchTest.php`. Espaces producteur / acheteur :
`tests/Feature/Producer/ProfileTest.php`, `tests/Feature/Buyer/ProfileTest.php` (activation,
édition, unicité par utilisateur, logo, `verified_at`/`verified_by` non modifiables, nav/CTA
selon l'état d'activation, cumul apprenant + producteur + acheteur). Offres producteur :
`tests/Feature/Producer/OffersTest.php` (création, validation quantité/prix, upload/suppression
de photos, plafond de 5 photos, propriété (policy), liste filtrée par producteur, bascule de
disponibilité, suppression, middleware `producer` sur les routes d'offres, nav). Besoins
acheteur : `tests/Feature/Buyer/NeedsTest.php` (publication, validation quantité/fréquence/
budget, édition, propriété (policy), liste filtrée par acheteur, suppression, middleware
`buyer` sur les routes de besoins, nav, commande `needs:expire-outdated`). Catalogues publics :
`tests/Feature/PublicMarketplaceTest.php` (`/producteurs`/`/besoins` — visibilité selon statut
publié/disponible/ouvert et compte suspendu, badge vérifié, filtres, pagination, aucune
coordonnée personnelle exposée, CTA + liens sur la page d'accueil). Mise en relation :
`tests/Feature/Connect/ConnectionRequestTest.php` — création (offre et besoin, exactement une
cible, anti-auto-mise-en-relation, anti-doublon d'une demande ouverte, ré-ouverture possible
après refus), cycle complet jusqu'à `collaboration_confirmee`, chaque transition testée à la
fois autorisée (bon acteur/bon statut) ET refusée (mauvais acteur, tiers, mauvais statut,
no-op idempotent), policy d'accès à l'écran de détail, aucune coordonnée personnelle affichée.
Messagerie : `tests/Unit/ContactDetectionTest.php` (détection pure — formats de téléphone,
e-mail, liens WhatsApp/Facebook/Instagram, tournures françaises, aucun faux positif sur un
message ordinaire, masquage sans toucher au reste du texte) et `tests/Feature/Connect/
MessagingTest.php` (échange entre les deux parties, conversation créée à la volée au premier
message, tiers et admin ne peuvent jamais écrire — admin lecture seule uniquement, texte
non masqué pour lui —, numéro de téléphone masqué à l'affichage mais conservé en clair en
base, jamais de blocage d'un message signalé, échappement HTML, compteur de signalements et
badge de modération au-delà du seuil). Collaboration : `tests/Feature/Connect/
CollaborationTest.php` — création automatique (+ idempotence) à la confirmation de la
demande via le vrai composant Livewire, cycle complet `en_cours` → `terminee` (paiement
déclaré/confirmé, 4 étapes de livraison sans saut possible), contestation d'un paiement
(retour à `en_cours`, pas litige), annulation limitée à `en_cours`, litige réservé à
l'admin et jamais à une partie, **l'admin ne peut jamais déclarer/confirmer/annuler à la
place d'une partie même si `Gate::before` bypasserait normalement** (double vérification
policy + garde-fou modèle), avertissement légal affiché, aucune coordonnée personnelle
exposée. Évaluations : `tests/Feature/Connect/ReviewTest.php` — impossible avant
`terminee`, direction/critères corrects selon qui note qui, une seule fois par partie,
tiers et admin ne peuvent jamais noter, moyenne/compteur du producteur mis à jour
uniquement par les avis reçus (pas ceux déposés), affichage + filtre par note sur le
catalogue public, aucune coordonnée personnelle exposée. Vérification producteur :
couverte par `tests/Feature/Admin/ConnectProducersTest.php` (voir Phase 10 plus bas —
l'ancien `ProducerVerificationTest.php` a été supprimé, fusionné dedans, pas doublé).
Notifications : `tests/Feature/Connect/NotificationsTest.php` — chacun des 8 événements
couverts crée la bonne `DatabaseNotification` pour le bon destinataire (celui qui n'a pas
agi, jamais l'auteur de l'action ni un tiers), les 3 événements à fort enjeu partent aussi
par e-mail (classe Mail correcte, bon destinataire), un SMTP en échec ne bloque jamais la
transition métier sous-jacente, aucun e-mail n'expose la coordonnée personnelle de l'autre
partie, et la cloche (`NotificationBell`) reflète le compteur non lu, marque une
notification (ou toutes) comme lue, et ne laisse jamais un utilisateur marquer comme lue
la notification d'un autre.
Supervision admin de la mise en relation (Phase 10) : `tests/Feature/Admin/
ConnectProducersTest.php` (liste + recherche + vérification/révocation depuis la liste ET
la fiche détail, badge public, accès réservé à l'admin — fusionne et remplace l'ancien
`ProducerVerificationTest.php`), `ConnectBuyersTest.php` (liste + recherche, suspension/
réactivation, garde-fous anti-auto-suspension, filtre suspendus), `ConnectCropOffersTest.php`/
`ConnectBuyerNeedsTest.php` (liste + recherche + filtre par statut, archivage/restauration
ou fermeture/réouverture), `ConnectConnectionRequestsTest.php` (supervision passive —
liste + filtre + lien vers l'écran partagé, aucune action de mutation, admin peut ouvrir le
détail en lecture), `ConnectCollaborationsTest.php` (onglet litiges par défaut, valeur
estimée avec repli sur le prix indicatif, résolution d'un litige — validation du motif,
no-op hors litige, refusée à une partie, journalisée), `ConnectStatsTest.php` (compteurs
plateforme, volume estimé excluant les collaborations annulées, accès réservé à l'admin).
Revue de clôture V1 (Phase 11) : pas de fichier dédié — densification des fichiers
existants (voir § Revue de clôture ci-dessus pour le détail des branches de refus
ajoutées à `ConnectionRequestTest`/`CollaborationTest`/`PaymentVerificationTest`/
`ConnectProducersTest`/`ProductOrderTest`/`MessagesTest`), plus le nouveau garde-fou
`Connect\Conversation` testé dans `MessagingTest::test_a_third_party_cannot_view_the_
composer_or_send` (un tiers qui atteint le composant ne voit plus le contenu des
messages), les 5 nouveaux rate limits testés dans `Producer\ProfileTest`/
`Buyer\ProfileTest`/`Producer\OffersTest`/`Buyer\NeedsTest`/`Connect\ReviewTest`
(`test_saving_*_is_rate_limited`/`test_submitting_a_review_is_rate_limited`),
l'indicateur communauté testé dans `HomePageTest` (affiché avec les vraies données,
masqué tant qu'elles sont à zéro), et la mention légale à l'activation testée dans
`Producer\ProfileTest`/`Buyer\ProfileTest` (`test_activation_is_blocked_without_
accepting_the_terms`) + les pages `/mentions-legales`/`/politique-de-confidentialite`
testées dans `PublicPagesTest` (message d'attente tant que le CMS est vide, contenu
réel une fois rempli, liens du pied de page).

## Ne pas faire

- Ne pas exécuter `DemoSeeder` en production (il ne tourne qu'en `local`/`testing`).
- Ne pas mettre de contenu éditorial en dur dans les Blade — passer par `SiteContent`.
