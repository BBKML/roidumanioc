<?php

namespace Database\Seeders;

use App\Models\Award;
use App\Models\SiteContent;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Reprend l'objet siteContent de la maquette (tableau-de-bord.html).
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            'seo' => ['label' => 'Référencement (SEO)', 'data' => [
                'title' => 'Le Roi du Manioc',
                'description' => "Royaume du Manioc d'Afrique — formations, marketplace, intrants et le Placali du Roi. Valoriser le manioc africain pour nourrir l'Afrique et le monde.",
            ]],
            'entete' => ['label' => 'En-tête & identité', 'data' => [
                'brand' => 'Le Roi du Manioc',
                'tagline' => "L'or des visionnaires",
                'menu' => ['Placali du Roi', 'Formations', 'Marketplace', 'Communauté', 'Le fondateur'],
                'cta' => 'Rejoindre le royaume',
            ]],
            'hero' => ['label' => 'Accueil — bandeau principal', 'data' => [
                'eyebrow' => "Royaume du Manioc d'Afrique",
                'title' => 'Le manioc, matière royale.',
                'title_html' => 'Le manioc,<br>matière <em>royale</em>.',
                'text' => "Formations, marketplace, intrants certifiés et notre Placali du Roi : tout l'écosystème pour réussir dans la culture du manioc et en vivre dignement.",
                'button1' => 'Découvrir le Placali du Roi',
                'button2' => 'Accéder aux formations',
                'trust' => ["Lauréat Éléphant d'Or 2025", '+15 000 membres actifs', '+2 500 producteurs'],
                'image' => ['src' => 'img/champ-manioc.jpg', 'alt' => 'Champ de manioc dense arrivé à maturité'],
                'portrait' => ['src' => 'img/fondateur-portrait.jpg', 'alt' => 'Seggo Kobenan Michel, fondateur du Roi du Manioc'],
            ]],
            'bandeau' => ['label' => 'Bandeau « Ils nous distinguent »', 'data' => [
                'items' => ["Éléphant d'Or 2025", 'OPAJEF', 'Institut Panafricain de la Biographie', 'Enabel · Union Européenne'],
            ]],
            'mission' => ['label' => "Notre raison d'être", 'data' => [
                'eyebrow' => "Notre raison d'être",
                'title' => 'Faire du manioc une filière de fierté et de revenus.',
                'p1' => "Le manioc nourrit l'Afrique depuis des générations, sans jamais recevoir la considération qu'il mérite. Nous formons les producteurs, structurons les débouchés et transformons la racine en produits de qualité royale.",
                'p2' => "De la bouture à l'assiette, chaque maillon est accompagné : savoir-faire agricole, intrants fiables, marché équitable et marque forte.",
                'quote' => "Valoriser le manioc africain pour nourrir l'Afrique et le monde.",
                'image' => ['src' => 'img/producteur-agent.jpg', 'alt' => 'Un producteur et un agent du Roi du Manioc côte à côte au village'],
            ]],
            'chiffres' => ['label' => 'Les chiffres clés', 'data' => ['items' => [
                ['value' => '15 000+', 'label' => 'Membres actifs'],
                ['value' => '2 500+', 'label' => 'Producteurs accompagnés'],
                ['value' => '300+', 'label' => 'Offres sur la marketplace'],
                ['value' => '120+', 'label' => 'Formations disponibles'],
            ]]],
            'piliers' => ['label' => 'Les 4 piliers', 'data' => [
                'eyebrow' => 'Ce que nous faisons',
                'title' => 'Quatre piliers, un même royaume.',
                'lead' => 'Chaque activité renforce les autres — le producteur formé vend mieux, le marché nourrit la marque, la marque finance la formation.',
                'cards' => [
                    ['title' => 'Formations', 'text' => 'Culture, fertilisation, transformation et commercialisation, expliquées pas à pas par des formateurs de terrain.', 'image' => ['src' => 'img/formation-champ.jpg', 'alt' => 'Formation pratique au champ']],
                    ['title' => 'Marketplace', 'text' => 'Manioc frais, attiéké, gari, placali : achetez et vendez au juste prix, entre producteurs vérifiés.', 'image' => ['src' => 'img/recolte-village.jpg', 'alt' => 'Récolte de manioc frais au village']],
                    ['title' => 'Boutures & Intrants', 'text' => 'Boutures améliorées, engrais organique, fongicide et répulsif naturels de la boutique officielle.', 'image' => ['src' => 'img/boutique-distinctions.jpg', 'alt' => 'Étal de la boutique officielle']],
                    ['title' => 'Placali du Roi', 'text' => 'Notre placali instantané, 100 % manioc naturel, prêt en quelques minutes. Le goût authentique, la qualité supérieure.', 'image' => ['src' => 'img/placali-sachet.jpg', 'alt' => 'Sachet de Placali du Roi']],
                ],
            ]],
            'placali' => ['label' => 'Placali du Roi', 'data' => [
                'eyebrow' => 'Le produit signature',
                'title' => 'Placali du Roi',
                'text' => 'Le vrai placali, prêt en 10 à 15 minutes. Sans conservateur, sans additif — juste du manioc africain, valorisé comme il se doit.',
                'atouts' => ['100 % manioc naturel', 'Goût authentique', 'Sans conservateur', 'Sans additif', 'Prêt en 10–15 min'],
                'price' => '1 500 FCFA',
                'price_detail' => '/ portion · sachet 1 kg',
                'button' => 'Découvrir le Placali du Roi',
                'image_bg' => ['src' => 'img/placali-champ.jpg', 'alt' => 'Producteur avec le Placali du Roi au champ'],
                'image_product' => ['src' => 'img/placali-etal.jpg', 'alt' => 'Sachet de Placali du Roi posé sur un étal'],
            ]],
            'formations_section' => ['label' => 'Section Formations (titres)', 'data' => [
                'eyebrow' => 'Formations',
                'title' => 'Apprendre le manioc, sérieusement.',
                'lead' => "Des parcours courts et concrets, du premier plant jusqu'à la vente. Deux formations d'introduction sont entièrement gratuites.",
                'image' => ['src' => 'img/formation-champ.jpg', 'alt' => 'Formation pratique dans un champ de manioc'],
            ]],
            'marketplace_section' => ['label' => 'Section Marketplace (titres)', 'data' => [
                'eyebrow' => 'Marketplace',
                'title' => 'Le marché du manioc, en confiance.',
                'lead' => 'Producteurs vérifiés, prix affichés, livraison organisée. Un aperçu des offres du moment.',
                'image' => ['src' => 'img/attieke-marche.jpg', 'alt' => "Vente d'attiéké et de manioc au marché"],
            ]],
            'evenements_section' => ['label' => 'Section Événements (titres)', 'data' => [
                'eyebrow' => 'Événements & rencontres',
                'title' => 'Les prochains<br><em>rendez-vous</em> du royaume.',
                'lead' => 'Rencontres, formations en présentiel, foires et journées portes ouvertes : suivez ici les activités à venir du Roi du Manioc.',
            ]],
            'producteurs_section' => ['label' => 'Section Producteurs & Acheteurs', 'data' => [
                'eyebrow' => 'Producteurs & acheteurs',
                'title' => 'Vendez ou achetez, directement entre vous.',
                'lead' => 'Chaque producteur peut publier ses récoltes, chaque acheteur peut publier ses besoins — sans intermédiaire, sans commission cachée.',
                'image' => ['src' => 'img/recolte-village.jpg', 'alt' => 'Récolte de manioc dans un village du royaume'],
                'opportunities' => [
                    'Vendez vos récoltes, boutures, produits transformés, intrants ou animaux d\'élevage directement aux acheteurs.',
                    'Trouvez des acheteurs fiables et faites connaître votre exploitation.',
                    "Publiez vos besoins d'achat et laissez les producteurs vérifiés vous répondre.",
                ],
                'relation_text' => "Le Roi du Manioc ne touche pas l'argent : nous mettons en relation, la transaction se fait directement entre vous.",
                'button_producer' => 'Devenir producteur',
                'button_buyer' => 'Devenir acheteur',
            ]],
            'communaute' => ['label' => 'Communauté & témoignages', 'data' => [
                'eyebrow' => 'La communauté',
                'title' => 'On avance mieux ensemble.',
                'lead' => 'Producteurs, transformatrices, acheteurs : la communauté partage questions, conseils et réussites au quotidien.',
                'image' => ['src' => 'img/communaute-champ.jpg', 'alt' => 'La communauté des producteurs réunie dans un champ de manioc'],
            ]],
            'distinctions' => ['label' => 'Distinctions', 'data' => [
                'eyebrow' => 'Reconnaissance',
                'title' => 'Un travail salué au plus haut niveau.',
                'image' => ['src' => 'img/ceremonie-attestations.jpg', 'alt' => 'Producteurs recevant leurs attestations lors d\'une cérémonie'],
            ]],
            'fondateur' => ['label' => 'Le fondateur', 'data' => [
                'eyebrow' => 'Le fondateur',
                'quote' => "La clé du succès en agriculture, c'est la régularité et la bonne information. Le manioc peut nourrir un continent — à nous de lui rendre sa couronne.",
                'name' => 'Seggo Kobenan Michel',
                'role' => "Fondateur du Royaume du Manioc d'Afrique · Formateur · Spécialiste du manioc",
                'image' => ['src' => 'img/fondateur-conference.jpg', 'alt' => 'Seggo Kobenan Michel lors d\'une formation'],
            ]],
            'cta' => ['label' => "Appel à l'action final", 'data' => [
                'eyebrow' => 'Rejoindre le royaume',
                'title' => 'Prêt à faire du manioc votre réussite ?',
                'lead' => "Créez votre compte gratuit : accès aux formations d'introduction, à la communauté et à la marketplace.",
                'button1' => 'Créer mon compte gratuit',
                'button2' => 'Nous contacter',
            ]],
            'pied' => ['label' => 'Pied de page', 'data' => [
                'description' => "Royaume du Manioc d'Afrique — valoriser le manioc africain pour nourrir l'Afrique et le monde.",
                'tiktok' => 'https://tiktok.com/@roidumanioc',
                'facebook' => 'https://facebook.com/roidumanioc',
                'youtube' => 'https://youtube.com/@roidumanioc',
                'instagram' => 'https://instagram.com/roidumanioc',
                'city' => "Yamoussoukro, Côte d'Ivoire",
                'email' => 'contact@roidumanioc.ci',
                'hours' => 'Lun–Ven, 8h–17h',
                'newsletter' => 'Recevez nos conseils manioc chaque semaine.',
                'copyright' => "© 2026 Royaume du Manioc d'Afrique. Tous droits réservés.",
                'map_lat' => '6.8206',
                'map_lng' => '-5.2767',
                'map_zoom' => '14',
            ]],
            // Texte fourni par Le Roi du Manioc — volontairement vide au seed, jamais rédigé ici (§44).
            'legal' => ['label' => 'Mentions légales & confidentialité', 'data' => [
                'mentions_legales' => '',
                'politique_confidentialite' => '',
                'charte_utilisation' => '',
            ]],
        ];

        $pos = 0;
        foreach ($sections as $key => $section) {
            SiteContent::updateOrCreate(
                ['key' => $key],
                ['label' => $section['label'], 'data' => SiteContent::wrapMonolingual($section['data'], $key), 'position' => $pos++],
            );
        }

        // Version anglaise volontairement vide : SiteContent::localizeSection() replie sur le
        // français tant que l'admin n'a pas rempli la traduction dans l'écran "Contenu du site".
        $testimonials = [
            ['quote' => 'Merci pour la formation sur la fertilisation, mes plants sont en pleine croissance !', 'author_name' => 'Ahou S.', 'author_role' => 'Productrice · Adzopé'],
            ['quote' => "J'ai posé une question sur mes feuilles qui jaunissaient, j'ai eu trois réponses utiles le jour même.", 'author_name' => 'Kouassi D.', 'author_role' => 'Producteur · Daloa'],
            ['quote' => 'Le Placali du Roi se vend seul sur mon étal. Les clientes reviennent pour la marque.', 'author_name' => 'Mariam F.', 'author_role' => 'Revendeuse · Yamoussoukro'],
        ];
        Testimonial::query()->delete();
        foreach ($testimonials as $i => $t) {
            Testimonial::create(array_merge($t, ['position' => $i]));
        }

        $awards = [
            ['year' => '2025', 'title' => 'Éléphant d\'Or — Super Prix National', 'description' => "Décerné par l'OPAJEF pour l'impact du Royaume du Manioc d'Afrique."],
            ['year' => '2025', 'title' => 'Ambassadeur de la Paix et du Développement', 'description' => "Attestation de l'Organisation Panafricaine de la Jeunesse Francophone."],
            ['year' => '2025', 'title' => "Commandeur de l'Ordre Africain de la Valeur", 'description' => 'Institut Panafricain de la Biographie (IPAB).'],
            ['year' => '—', 'title' => 'Partenariat Bioseft-Fertilisant', 'description' => "Avec Enabel et l'Union Européenne, pour un intrant organique innovant."],
        ];
        Award::query()->delete();
        foreach ($awards as $i => $a) {
            Award::create(array_merge($a, ['position' => $i]));
        }
    }
}
