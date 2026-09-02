# Le Roi du Manioc — Application

Reconstruction en Laravel de la maquette (`../index.html` + `../tableau-de-bord.html`).
Voir la **feuille de route** (9 phases) pour le plan complet.

- **Laravel** 13 · **PHP** 8.3 · **MySQL** 8
- **Front** : Blade + Livewire 3 + Tailwind (Vite)
- **Auth** : Laravel Breeze (Blade) + 2 rôles : `admin`, `apprenant`

État : **Phase 1 terminée** — fondations (base de données, modèles, seeders, auth, squelette de routes).

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

16 tables métier. Correspondance avec la maquette :

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

### Le cœur métier — paiement manuel

`app/Models/Payment.php` :

- `Payment::generateReference()` → `RDM-XXXX`
- `->runAutoCheck()` → compare le **montant déclaré** au **montant attendu** (badge conforme / insuffisant / excédent). *Ne prouve pas que l'argent est arrivé.*
- `->confirm($admin)` → passe le paiement en `confirme` **et débloque** l'inscription (`enrollment->markValidated()`) ou la commande (`order->markValidated()`)
- `->reject($admin)` → refuse et annule l'inscription / la commande
- `->whatsappLink()` → lien `wa.me` pré-rempli

### Autorisations

- `Gate::before` (dans `AppServiceProvider`) : un **admin actif** passe tout.
- Middleware `admin` (alias dans `bootstrap/app.php`) sur le groupe `/admin`.
- `FormationPolicy@follow` : un apprenant n'accède aux leçons que si la formation est
  gratuite **ou** qu'il a une inscription `validee`.

---

## Structure des routes

| Préfixe        | Middleware              | Contenu |
|----------------|-------------------------|---------|
| `/`            | —                       | site vitrine (`HomeController`) |
| `/dashboard`   | `auth`                  | redirige vers l'espace selon le rôle |
| `/mon-espace`  | `auth`, `verified`      | espace apprenant |
| `/admin`       | `auth`, `verified`, `admin` | administration |

---

## Déploiement LWS

Voir `.env.production.example`. Points clés :

- `NODE_ENV=development npm run build` **en local**, puis uploader `public/build/`
- `composer install --no-dev --optimize-autoloader` (ou uploader `vendor/` si pas de Composer sur le serveur)
- Racine web du domaine → `/public`
- Cron unique : `* * * * * php /chemin/artisan schedule:run`
- File d'attente : `QUEUE_CONNECTION=database` + cron `queue:work --stop-when-empty --max-time=50`
- `php artisan migrate --force` puis `php artisan db:seed --force` (⚠️ le `DemoSeeder` ne
  s'exécute **pas** hors `local`/`testing`)
- `php artisan config:cache route:cache view:cache`

---

## Prochaines phases

- **2** — Porter la vitrine (`index.html`) sur `resources/views/home.blade.php`
- **3** — Back-office Livewire : contenu du site, formations/leçons, boutique, marketplace, communauté, événements
- **4** — Espace apprenant : catalogue, lecteur de formation, progression
- **5** — Parcours de paiement manuel complet (upload preuve, contrôle, file « à vérifier »)
- **6** — Gestion des comptes (mon compte, membres, Google Sign-In)
- **7** — Sécurité, perf, sauvegardes
- **8** — Mise en production LWS
