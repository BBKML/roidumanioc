# Le Roi du Manioc — repères pour l'IA / les devs

Application Laravel 13 (Blade + Livewire 3 + Tailwind, MySQL). Reconstruit la maquette
`../index.html` (vitrine) et `../tableau-de-bord.html` (admin + apprenant).
Voir `README.md` pour l'installation et le mapping des tables.

## Conventions

- **Statuts = enums PHP** dans `app/Enums/`, colonnes `string` en base.
- **Rôles** : `admin`, `apprenant` (`users.role`). Un admin actif passe toutes les Gates
  (`AppServiceProvider::boot`). Middleware `admin` sur `/admin`.
- **Vitrine 100 % base de données** : `SiteContent` (CMS, 1 ligne/section), `Testimonial`,
  `Award`, + les modèles catalogue. Rien en dur dans les vues.
- **Paiement manuel** : toute la logique est sur `App\Models\Payment`
  (`runAutoCheck`, `confirm`, `reject`, `whatsappLink`). `confirm()` débloque l'inscription
  ou la commande liée.
- Français partout dans l'UI et les libellés.

## Gotcha environnement

`NODE_ENV=production` peut être défini globalement → `npm install` saute les devDependencies.
Toujours : `NODE_ENV=development npm install --include=dev` puis `npm run build`.

## Tests

`php artisan test` — 31 verts. Le flux critique (paiement → confirmation → accès formation)
est couvert par `tests/Feature/PaymentFlowTest.php`.

## Ne pas faire

- Ne pas exécuter `DemoSeeder` en production (il ne tourne qu'en `local`/`testing`).
- Ne pas mettre de contenu éditorial en dur dans les Blade — passer par `SiteContent`.
