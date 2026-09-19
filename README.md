# Le Roi du Manioc — Application

Reconstruction en Laravel de la maquette (`../index.html` + `../tableau-de-bord.html`).
Voir la **feuille de route** (9 phases) pour le plan complet.

- **Laravel** 13 · **PHP** 8.3 · **MySQL** 8
- **Front** : Blade + Livewire 4 (Vite, CSS maison)
- **Auth** : Laravel Breeze (Blade) + 2 rôles : `admin`, `apprenant`

État : **Phase 7 terminée** — durcissement (en-têtes + CSP, politique de mot de passe, journal
d'audit admin, `route:cache`, N+1), **sauvegardes** autonomes planifiées (`backup:run`), et
**média de leçon** : chaque leçon accepte un **lien** (YouTube/Vimeo), un **fichier vidéo
téléversé** (privé, servi par une route gardée, filigrane sur les cours payants), une source
**Bunny Stream** (URL signées) ou un/des **PDF joints** ; une leçon peut être de type *Document*.
Phase 6 : gestion des comptes (Mon compte, Membres + gardes, connexion Google).
Phase 5 : paiement manuel complet + anti-fraude (upload preuve, contrôle auto, file admin, e-mails).
Phase 4 : espace apprenant (catalogue, lecteur de formation + progression, marketplace, communauté).
Phase 3 : back-office Livewire (CMS, formations/leçons, boutique, modération, événements, paramètres).
Phase 2 : vitrine sur Blade (100 % BDD), SEO/OG, `sitemap.xml`, contact + infolettre.
Phase 1 : fondations (base de données, modèles, seeders, auth).

---

## Installation (local)

```bash
composer install

# ⚠️ L'environnement de dev peut avoir NODE_ENV=production, ce qui fait que
#    "npm install" saute les devDependencies (vite, tailwind…). Forcer :
NODE_ENV=development npm install --include=dev
NODE_ENV=development npm run build      # ou : npm run dev

cp .env.example .env
php artisan key:generate

# Base : créer une base MySQL "roi_manioc_db" puis ajuster DB_* dans .env
php artisan migrate:fresh --seed        # inclut les données de démo en local
php artisan storage:link

php artisan serve
```

### Comptes de démonstration

| Rôle       | E-mail                    | Mot de passe |
|------------|---------------------------|--------------|
| Admin      | `admin@roidumanioc.ci`    | `admin`      |
| Apprenante | `aicha@exemple.ci`        | `demo`       |

---

## Base de données

~24 tables métier. Correspondance avec la maquette :

| Table                   | Maquette (`DB.*`)      | Rôle |
|-------------------------|------------------------|------|
| `users`                 | `membres`              | comptes (+ `role`, `status`, `phone`, `city`, `provider`) |
| `formations`            | `formations`           | parcours de formation |
| `lessons`               | `formations[].lecons`  | leçons (vidéo / quiz) |
| `enrollments`           | `inscriptions`         | inscription apprenant → formation (`paiement`/`validee`/`refuse`) |
| `lesson_progress`       | `progress`             | leçons terminées (1 ligne / leçon) |
| `marketplace_listings`  | `offres`               | annonces des producteurs (modérées) |
| `shop_products`         | `produits`             | boutique officielle (intrants) |
| `orders`                | `commandes`            | commandes produit / annonce |
| `payments`              | `paiements`            | preuve de paiement + contrôle auto + confirmation |
| `payment_settings`      | `paiementsConfig`      | comptes marchands (1 ligne) |
| `site_contents`         | `siteContent`          | CMS du site vitrine (1 ligne / section, `data` JSON) |
| `testimonials`          | `siteContent.communaute.temoignages` | témoignages |
| `awards`                | `siteContent.distinctions.prix`       | distinctions |
| `community_posts`       | `posts`                | publications communauté |
| `community_replies`     | —                      | réponses (prévu) |
| `events`                | `evenements`           | lives / ateliers / visites |

Les statuts sont des **enums PHP** (`app/Enums/`), stockés en colonnes `string` pour rester
souples au niveau base (pas d'ALTER pénible en prod).

### Le cœur métier — paiement manuel & anti-fraude

**Principe :** la capture d'écran ne prouve rien. Seul l'admin qui voit l'argent sur le compte
marchand confirme. L'automatique ne fait que **trier**.

`app/Actions/DeclarePayment.php` — point d'entrée unique (formation ou produit) :

- le **montant attendu est calculé côté serveur** (jamais celui envoyé par le client) ;
- un seul paiement `a_verifier` par (client, objet) ; compte suspendu bloqué ; rate limit (5/h) ;
- la capture est stockée sur le disque **privé** `local` (jamais exposée au web) + hash SHA-256 ;
- crée l'`Enrollment` (`paiement`) ou l'`Order` (`paiement`) + le `Payment` (`a_verifier`).

`app/Models/Payment.php` :

- `->runAutoCheck()` → `check_result` : `amount` (ok/insufficient/excess), `flags`
  (`duplicate_transaction`, `duplicate_proof`, `multiple_pending`, `proof_missing`…) et
  `risk` (low / medium / high).
- `->confirm($admin)` / `->reject($admin, $reason)` → **idempotents** (no-op si le paiement
  n'est plus `a_verifier`), transaction DB, `LogsActivity`. `confirm()` débloque l'inscription
  ou la commande liée.
- `->canBeViewedBy($user)` → admin **ou** propriétaire (route `payments.proof`).

Écran admin `Livewire\Admin\Payments` : verdict + signaux affichés, aperçu de la capture,
**case « j'ai vérifié la réception » obligatoire** avant le bouton Confirmer.
E-mails : `app/Mail/PaymentSubmittedMail`, `PaymentConfirmedMail`, `PaymentRejectedMail`,
`NewPaymentToVerifyMail`.

### Autorisations

- `Gate::before` (dans `AppServiceProvider`) : un **admin actif** passe tout.
- Middlewares `admin` (rôle) et `active` (bloque les comptes suspendus), alias dans
  `bootstrap/app.php`. Rendus **persistants Livewire** dans `AppServiceProvider` : re-vérifiés
  à chaque `wire:click`, pas seulement au chargement de la page.
- `FormationPolicy@follow` : un apprenant n'accède aux leçons que si la formation est
  gratuite **ou** qu'il a une inscription `validee`.
- Gestion des comptes : `role`/`status` ne sont jamais renseignés depuis une requête
  utilisateur ; `User::isLastActiveAdmin()` empêche de se retrouver sans administrateur.

### Connexion Google (optionnelle)

Créer un ID OAuth « Application Web » sur console.cloud.google.com, autoriser l'URI de redirection
`https://VOTRE-DOMAINE/auth/google/callback`, puis renseigner `GOOGLE_CLIENT_ID` /
`GOOGLE_CLIENT_SECRET` dans `.env`. Sans ces valeurs, le bouton est masqué et les routes
renvoient 404. Un compte créé via Google est toujours un **apprenant actif** ; la liaison à un
compte existant se fait uniquement sur une adresse Google **vérifiée**.

---

## Structure des routes

| Préfixe        | Middleware              | Contenu |
|----------------|-------------------------|---------|
| `/`            | —                       | site vitrine (`HomeController`), 100 % base de données |
| `/contact`     | — (`throttle` sur POST) | formulaire de contact → e-mail + table `contact_messages` |
| `/newsletter`  | `throttle` (POST)       | inscription infolettre → table `newsletter_subscribers` |
| `/sitemap.xml` | —                       | `SitemapController` (XML dynamique) |
| `/dashboard`   | `auth`                  | redirige vers l'espace selon le rôle |
| `/auth/google/{redirect,callback}` | `guest` | connexion Google (si `GOOGLE_CLIENT_ID` défini) |
| `/mon-compte`  | `auth`, `active`        | Mon compte (apprenant & admin) — `Livewire\Account\Settings` |
| `/mon-espace/…` (catalogue, formations/{f}, formations/{f}/paiement, boutique/{p}/paiement, progression, marketplace, commandes, communaute) | `auth`, `active` | espace apprenant Livewire (`app/Livewire/Learner/`) |
| `/paiements/{payment}/preuve` | `auth` | capture de paiement — admin ou propriétaire uniquement |
| `/admin/…` (paiements, commandes, membres, contenu, formations, formations/{f}/lecons, marketplace, boutique, evenements, communaute, parametres) | `auth`, `admin` | back-office Livewire (`app/Livewire/Admin/`) |

Back-office et espace apprenant : composants Livewire *classe*, layouts
`components.layouts.{admin,learner}`, assets partagés `resources/{css/admin.css,js/admin.js}`.
Le CMS « Contenu du site » (`ContentManager`) édite `site_contents.data` puis vide le cache
`site_content` → la vitrine se met à jour immédiatement. Formation gratuite → inscription
immédiate ; premium → `Checkout` (Enrollment `paiement` + Payment `a_verifier`, confirmation
admin en Phase 5).

La vitrine partage un layout unique : `resources/views/components/public-layout.blade.php`
(en-tête vert foncé + pied de page + SEO/Open Graph dynamiques depuis `SiteContent`).
Assets front dédiés : `resources/css/vitrine.css` + `resources/js/vitrine.js` (entrées Vite séparées
du back-office).

---

## Déploiement LWS

Voir `.env.production.example`. Points clés :

- `NODE_ENV=development npm run build` **en local**, puis uploader `public/build/`
- `composer install --no-dev --optimize-autoloader` (ou uploader `vendor/` si pas de Composer sur le serveur)
- Racine web du domaine → `/public`
- Cron `schedule:run` : `* * * * * php /chemin/artisan schedule:run` → lance backup:run, activitylog:clean et payments:expire-pending
  (03h00, → `storage/app/backups/`) et `activitylog:clean` (lundi). Régler `DB_DUMP_BINARY`
  si `mysqldump` n'est pas dans le PATH.
- File d'attente : `QUEUE_CONNECTION=database` + cron `queue:work --stop-when-empty --max-time=55`
- `php artisan migrate --force` puis `php artisan db:seed --force` (⚠️ le `DemoSeeder` ne
  s'exécute **pas** hors `local`/`testing`)
- `php artisan config:cache route:cache view:cache event:cache` (la route `/dashboard` est
  désormais un contrôleur → `route:cache` OK)
- Vérifier les en-têtes de sécurité (`SecurityHeaders`) et `SESSION_SECURE_COOKIE=true` sous HTTPS
- Vidéos : renseigner `BUNNY_STREAM_*` pour des lectures signées (sinon YouTube/Vimeo restent gérés)

---

## Prochaines phases

- **2** — ~~Vitrine sur Blade + SEO + contact~~ ✅
- **3** — ~~Back-office Livewire : contenu du site, formations/leçons, boutique, marketplace, communauté, événements, paramètres~~ ✅
- **4** — ~~Espace apprenant : catalogue, lecteur de formation, progression~~ ✅
- **5** — ~~Parcours de paiement manuel complet + anti-fraude : upload preuve, contrôle auto,
  file admin, confirmation → déblocage, e-mails, commandes produits~~ ✅
- **6** — ~~Gestion des comptes : mon compte, membres, rôles, Google Sign-In~~ ✅
- **7** — ~~Sécurité (en-têtes/CSP, mots de passe, audit), perf, sauvegardes, **média de leçon** (lien / upload privé / Bunny signé + PDF joints)~~ ✅
- **8** — Mise en production LWS (déploiement, cron, monitoring, HTTPS) — *en attente : hébergement LWS à souscrire par le propriétaire*
