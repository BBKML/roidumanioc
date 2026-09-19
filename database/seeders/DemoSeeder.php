<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MarketplaceListing;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration (inscriptions, progression, paiements à vérifier).
 * NE PAS exécuter en production — voir DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $aicha = User::where('email', 'aicha@exemple.ci')->first();
        $kouassi = User::where('email', 'kouassi@exemple.ci')->first();
        $ahou = User::where('email', 'ahou@exemple.ci')->first();
        $konan = User::where('email', 'konan@exemple.ci')->first();

        $f1 = Formation::where('slug', 'reussir-la-culture-du-manioc')->first();
        $f2 = Formation::where('slug', 'fertilisation-et-rendement')->first();
        $f5 = Formation::where('slug', 'les-bases-de-la-culture-du-manioc')->first();
        $f6 = Formation::where('slug', 'preparation-du-sol-et-plantation')->first();

        // --- Inscriptions validées ---
        foreach ([[$aicha, $f1], [$aicha, $f5], [$konan, $f6], [$ahou, $f2]] as [$user, $formation]) {
            Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'formation_id' => $formation->id],
                ['status' => 'validee', 'enrolled_at' => now()->subDays(rand(5, 40))],
            );
        }

        // --- Progression d'Aïcha ---
        $done = Lesson::where('formation_id', $f1->id)->orderBy('position')->take(2)->get()
            ->merge(Lesson::where('formation_id', $f5->id)->orderBy('position')->take(1)->get());
        foreach ($done as $lesson) {
            LessonProgress::updateOrCreate(
                ['user_id' => $aicha->id, 'lesson_id' => $lesson->id],
                ['completed_at' => now()->subDays(rand(1, 10))],
            );
        }

        // --- Paiement formation à vérifier (Kouassi → f1), montant conforme ---
        $enr = Enrollment::updateOrCreate(
            ['user_id' => $kouassi->id, 'formation_id' => $f1->id],
            ['status' => 'paiement'],
        );
        $p1 = Payment::updateOrCreate(
            ['reference' => 'RDM-7412'],
            [
                'user_id' => $kouassi->id,
                'payable_type' => Formation::class, 'payable_id' => $f1->id,
                'enrollment_id' => $enr->id,
                'label' => 'Formation — '.$f1->title,
                'amount' => $f1->price, 'quantity' => 1, 'method' => 'wave', 'status' => 'a_verifier',
                'declared_amount' => $f1->price, 'transaction_id' => 'TX480021573',
                'submitted_at' => now()->subHours(3),
            ],
        );
        $p1->runAutoCheck();
        $p1->save();

        // --- Paiement produit à vérifier, montant insuffisant (Ahou → engrais) ---
        $engrais = ShopProduct::where('name', 'like', 'Engrais%')->first();
        $order = Order::updateOrCreate(
            ['reference' => 'CMD-3925'],
            [
                'user_id' => $ahou->id, 'orderable_type' => ShopProduct::class, 'orderable_id' => $engrais->id,
                'customer_name' => $ahou->name, 'contact_phone' => $ahou->phone,
                'delivery_address' => 'Marché central', 'delivery_city' => 'Adzopé',
                'item_label' => $engrais->name.' ×1',
                'quantity' => 1, 'amount' => $engrais->price, 'delivery_fee' => 0,
                'payment_mode' => 'online', 'status' => 'paiement',
                'ordered_at' => now()->subHours(4),
            ],
        );
        $p2 = Payment::updateOrCreate(
            ['reference' => 'RDM-3925'],
            [
                'user_id' => $ahou->id,
                'payable_type' => ShopProduct::class, 'payable_id' => $engrais->id,
                'order_id' => $order->id,
                'label' => $engrais->name.' ×1',
                'amount' => $engrais->price, 'quantity' => 1, 'method' => 'orange_money', 'status' => 'a_verifier',
                'declared_amount' => 10000, 'transaction_id' => 'OM99120034',
                'submitted_at' => now()->subHours(4),
            ],
        );
        $p2->runAutoCheck();
        $p2->save();

        // --- Un paiement déjà confirmé (Aïcha → f1) : trace la recette encaissée ---
        $aichaEnr = Enrollment::where('user_id', $aicha->id)->where('formation_id', $f1->id)->first();
        $p3 = Payment::updateOrCreate(
            ['reference' => 'RDM-6100'],
            [
                'user_id' => $aicha->id,
                'payable_type' => Formation::class, 'payable_id' => $f1->id,
                'enrollment_id' => $aichaEnr?->id,
                'label' => 'Formation — '.$f1->title,
                'amount' => $f1->price, 'quantity' => 1, 'method' => 'wave', 'status' => 'confirme',
                'declared_amount' => $f1->price, 'transaction_id' => 'TX477100200',
                'confirmed_by' => User::where('role', 'admin')->value('id'),
                'confirmed_at' => now()->subDays(12),
                'submitted_at' => now()->subDays(12),
                'check_result' => ['proof' => 'ok', 'amount' => 'ok', 'gap' => 0, 'declared' => $f1->price, 'flags' => [], 'risk' => 'low'],
            ],
        );

        // --- Commande « paiement à la livraison » à préparer (Kouassi) ---
        $boutures = ShopProduct::where('name', 'like', 'Boutures%')->first();
        Order::updateOrCreate(
            ['reference' => 'CMD-4180'],
            [
                'user_id' => $kouassi->id,
                'orderable_type' => ShopProduct::class, 'orderable_id' => $boutures?->id,
                'customer_name' => 'Kouassi Diby', 'contact_phone' => $kouassi->phone,
                'delivery_address' => 'Route de Zoukougbeu, après le pont', 'delivery_city' => 'Daloa',
                'item_label' => ($boutures?->name ?? 'Boutures').' ×300', 'quantity' => 300,
                'amount' => 150000, 'delivery_fee' => 2000,
                'payment_mode' => 'on_delivery', 'status' => 'validee',
                'ordered_at' => now()->subHours(20),
            ],
        );

        // --- Demande sur une annonce producteur (Aïcha → annonce de Kouassi) ---
        $listing = MarketplaceListing::where('status', 'validee')->first();
        if ($listing) {
            Order::updateOrCreate(
                ['reference' => 'CMD-7701'],
                [
                    'user_id' => $aicha->id,
                    'orderable_type' => MarketplaceListing::class, 'orderable_id' => $listing->id,
                    'customer_name' => $aicha->name, 'contact_phone' => $aicha->phone,
                    'delivery_address' => 'Rue des Jardins', 'delivery_city' => 'Yamoussoukro',
                    'item_label' => $listing->title.' — 500 kg', 'quantity' => 1,
                    'amount' => 0, 'delivery_fee' => 0,
                    'customer_note' => 'Disponible cette semaine, livraison si possible.',
                    'payment_mode' => 'direct', 'status' => 'validee',
                    'ordered_at' => now()->subHours(8),
                ],
            );
        }

        // --- Commande déjà livrée (Konan) ---
        Order::updateOrCreate(
            ['reference' => 'CMD-2050'],
            [
                'user_id' => $konan->id,
                'orderable_type' => ShopProduct::class, 'orderable_id' => $engrais->id,
                'customer_name' => 'Konan Ismaël', 'contact_phone' => $konan->phone,
                'delivery_address' => 'Quartier Air France', 'delivery_city' => 'Korhogo',
                'item_label' => $engrais->name.' ×2', 'quantity' => 2,
                'amount' => 24000, 'delivery_fee' => 2000,
                'payment_mode' => 'on_delivery', 'status' => 'livree',
                'ordered_at' => now()->subDays(6), 'delivered_at' => now()->subDays(4),
                'handled_by' => User::where('role', 'admin')->value('id'),
            ],
        );

        // --- Messages de contact ---
        ContactMessage::updateOrCreate(
            ['email' => 'yao.producteur@example.ci', 'subject' => 'Boutures pour 2 hectares'],
            [
                'name' => 'Yao N.', 'phone' => '07 45 67 89 01',
                'message' => 'Bonjour, je prépare 2 hectares à Divo. Avez-vous des boutures améliorées disponibles et quel est le délai de livraison ?',
                'status' => 'nouveau',
            ],
        );
        ContactMessage::updateOrCreate(
            ['email' => 'formation@example.ci', 'subject' => 'Formation transformation'],
            [
                'name' => 'Aya K.', 'phone' => null,
                'message' => 'La formation « Transformation du manioc » est-elle accessible depuis Bouaké ? Merci.',
                'status' => 'traite', 'replied_at' => now()->subDays(1),
                'handled_by' => User::where('role', 'admin')->value('id'), 'handled_at' => now()->subDays(1),
            ],
        );

        // --- Abonnés infolettre ---
        foreach ([
            ['contact1@example.ci', 'footer', null],
            ['contact2@example.ci', 'footer', null],
            ['ancien@example.ci', 'footer', now()->subMonth()],
        ] as [$email, $source, $unsub]) {
            NewsletterSubscriber::updateOrCreate(
                ['email' => $email],
                ['source' => $source, 'unsubscribed_at' => $unsub],
            );
        }
    }
}
