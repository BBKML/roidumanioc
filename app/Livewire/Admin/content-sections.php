<?php

/*
 * Schéma d'édition du contenu du site (CMS).
 * Chaque section reflète la structure réelle stockée dans site_contents.data
 * — celle que resources/views/home.blade.php consomme.
 *
 * Types de champ : text | textarea | html | list | image
 * (list et image sont traités à part dans la vue / le composant)
 */
return [
    'seo' => [
        'label' => 'Référencement (SEO)',
        'hint' => "titre d'onglet & description Google",
        'fields' => [
            ['title', 'text', "Titre affiché dans l'onglet du navigateur"],
            ['description', 'textarea', 'Description (extrait dans les résultats Google)'],
        ],
    ],
    'entete' => [
        'label' => 'En-tête & identité',
        'hint' => 'nom, slogan, menu, bouton',
        'fields' => [
            ['brand', 'text', 'Nom de la marque'],
            ['tagline', 'text', 'Slogan'],
            ['menu', 'list', 'Liens du menu (une entrée par ligne)'],
            ['cta', 'text', 'Texte du bouton principal'],
        ],
    ],
    'hero' => [
        'label' => 'Accueil — bandeau principal',
        'hint' => 'le grand titre en haut du site',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre (petit texte doré)'],
            ['title', 'text', 'Titre — texte simple (sert au SEO et au partage)'],
            ['title_html', 'html', 'Titre affiché — HTML autorisé (<br>, <em>)'],
            ['text', 'textarea', "Texte d'introduction"],
            ['button1', 'text', 'Bouton 1'],
            ['button2', 'text', 'Bouton 2'],
            ['trust', 'list', 'Ligne de confiance sous les boutons (une entrée par ligne)'],
            ['image', 'image', 'Image principale (champ)'],
            ['portrait', 'image', 'Portrait rond (fondateur)'],
        ],
    ],
    'bandeau' => [
        'label' => 'Bandeau « Ils nous distinguent »',
        'hint' => 'mentions défilant sous le hero',
        'fields' => [
            ['items', 'list', 'Mentions (une par ligne)'],
        ],
    ],
    'mission' => [
        'label' => "Notre raison d'être",
        'hint' => 'texte de mission + citation + image',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['p1', 'textarea', 'Paragraphe 1'],
            ['p2', 'textarea', 'Paragraphe 2'],
            ['quote', 'textarea', 'Citation encadrée'],
            ['image', 'image', 'Image'],
        ],
    ],
    'chiffres' => [
        'label' => 'Les chiffres clés',
        'hint' => 'les 4 grands nombres',
        'repeater' => ['path' => 'items', 'fixed' => true, 'label' => 'Chiffre', 'fields' => [
            ['value', 'text', 'Valeur'],
            ['label', 'text', 'Libellé'],
        ]],
        'fields' => [],
    ],
    'piliers' => [
        'label' => 'Les 4 piliers',
        'hint' => 'intro + les 4 cartes',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', "Texte d'intro"],
        ],
        'repeater' => ['path' => 'cards', 'fixed' => true, 'label' => 'Carte', 'fields' => [
            ['title', 'text', 'Titre'],
            ['text', 'textarea', 'Texte'],
            ['image', 'image', 'Image'],
        ]],
    ],
    'placali' => [
        'label' => 'Placali du Roi',
        'hint' => 'section produit signature',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['text', 'textarea', 'Description'],
            ['atouts', 'list', 'Étiquettes / atouts (une par ligne)'],
            ['price', 'text', 'Prix'],
            ['price_detail', 'text', 'Détail du prix'],
            ['button', 'text', 'Texte du bouton'],
            ['image_bg', 'image', 'Image de fond'],
            ['image_product', 'image', 'Photo du sachet'],
        ],
    ],
    'formations_section' => [
        'label' => 'Section Formations (titres)',
        'hint' => 'les fiches se gèrent dans « Formations » — image utilisée sur la page dédiée /formations',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', "Texte d'intro"],
            ['image', 'image', 'Photo du hero (page /formations)'],
        ],
    ],
    'marketplace_section' => [
        'label' => 'Section Marketplace (titres)',
        'hint' => 'les offres se gèrent dans « Marketplace » — image utilisée sur la page dédiée /marketplace',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', "Texte d'intro"],
            ['image', 'image', 'Photo du hero (page /marketplace)'],
        ],
    ],
    'evenements_section' => [
        'label' => 'Section Événements (titres)',
        'hint' => 'les événements se gèrent dans « Événements » plus bas — ici, juste les titres et la photo du hero de la page /evenements',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'html', 'Titre (balise <em> pour la partie en doré, <br> pour couper la ligne)'],
            ['lead', 'textarea', "Texte d'intro"],
            ['image', 'image', 'Photo du hero (page /evenements)'],
        ],
    ],
    'producteurs_section' => [
        'label' => 'Section Producteurs & Acheteurs',
        'hint' => 'les producteurs et besoins affichés se gèrent dans « Producteurs » / « Besoins » (catalogues publics) — ici, les titres, les opportunités, les boutons, et la photo utilisée sur l\'accueil et sur les pages dédiées /producteurs, /besoins',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', "Texte d'intro"],
            ['image', 'image', 'Photo (accueil + hero des pages /producteurs et /besoins)'],
            ['opportunities', 'list', 'Opportunités (une par ligne)'],
            ['relation_text', 'textarea', 'Texte « mise en relation directe »'],
            ['button_producer', 'text', 'Texte du bouton « Devenir producteur »'],
            ['button_buyer', 'text', 'Texte du bouton « Devenir acheteur »'],
        ],
    ],
    'communaute' => [
        'label' => 'Communauté',
        'hint' => 'titres + photo de groupe',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', "Texte d'intro"],
            ['image', 'image', 'Photo de groupe'],
        ],
    ],
    'distinctions' => [
        'label' => 'Distinctions (titres + image)',
        'hint' => 'la liste des prix est éditable plus bas',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['image', 'image', 'Image'],
        ],
    ],
    'fondateur' => [
        'label' => 'Le fondateur',
        'hint' => 'citation + nom + rôle + photo',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['quote', 'textarea', 'Citation'],
            ['name', 'text', 'Nom'],
            ['role', 'text', 'Rôle / fonction'],
            ['image', 'image', 'Photo'],
        ],
    ],
    'cta' => [
        'label' => "Appel à l'action (bas de page)",
        'hint' => 'le grand encart vert avant le pied de page',
        'fields' => [
            ['eyebrow', 'text', 'Sur-titre'],
            ['title', 'text', 'Titre'],
            ['lead', 'textarea', 'Texte'],
            ['button1', 'text', 'Bouton 1'],
            ['button2', 'text', 'Bouton 2'],
        ],
    ],
    'pied' => [
        'label' => 'Pied de page',
        'hint' => 'description, réseaux, contact, newsletter, carte de la page Contact',
        'fields' => [
            ['description', 'textarea', 'Phrase de description'],
            ['city', 'text', 'Ville / adresse'],
            ['email', 'text', 'E-mail de contact'],
            ['hours', 'text', 'Horaires (ex : Lun–Ven, 8h–17h) — facultatif'],
            ['tiktok', 'text', 'Lien TikTok'],
            ['facebook', 'text', 'Lien Facebook'],
            ['youtube', 'text', 'Lien YouTube'],
            ['instagram', 'text', 'Lien Instagram'],
            ['whatsapp', 'text', 'Numéro WhatsApp (ex : +225 07 00 00 00 00) ou lien complet'],
            ['linkedin', 'text', 'Lien LinkedIn'],
            ['newsletter', 'text', 'Accroche newsletter'],
            ['copyright', 'text', 'Mention de copyright'],
            // Carte de la page Contact — masquée si latitude/longitude absentes (cf. contact.blade.php).
            ['map_lat', 'text', 'Carte — latitude (ex : 6.8206)'],
            ['map_lng', 'text', 'Carte — longitude (ex : -5.2767)'],
            ['map_zoom', 'text', 'Carte — niveau de zoom (défaut 14)'],
        ],
    ],
    'legal' => [
        'label' => 'Mentions légales & confidentialité',
        'hint' => 'texte fourni par Le Roi du Manioc — à coller tel quel, jamais rédigé ici',
        'fields' => [
            ['mentions_legales', 'html', 'Mentions légales / CGU — HTML autorisé'],
            ['politique_confidentialite', 'html', 'Politique de confidentialité — HTML autorisé'],
            // Regroupe volontairement 4 sujets du §44 (audit V1) en un seul document — cf.
            // commentaire dans routes/web.php sur le choix de ne pas créer 4 pages distinctes.
            ['charte_utilisation', 'html', "Charte d'utilisation — règles de publication, règles de comportement, gestion des litiges et politique de paiement — HTML autorisé"],
        ],
    ],
];
