<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Lesson;
use App\Models\LessonProgress;
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

        // --- Paiement formation à vérifier (Kouassi → f1) ---
        $enr = Enrollment::updateOrCreate(
            ['user_id' => $kouassi->id, 'formation_id' => $f1->id],
            ['status' => 'paiement'],
        );
        Payment::updateOrCreate(
            ['reference' => 'RDM-7412'],
            [
                'user_id' => $kouassi->id,
                'payable_type' => Formation::class, 'payable_id' => $f1->id,
                'enrollment_id' => $enr->id,
                'label' => 'Formation — '.$f1->title,
                'amount' => $f1->price, 'method' => 'wave', 'status' => 'a_verifier',
                'declared_amount' => $f1->price, 'transaction_id' => 'TX480021573',
                'check_result' => ['proof' => 'ok', 'amount' => 'ok', 'gap' => 0, 'declared' => $f1->price],
                'submitted_at' => now()->subHours(3),
            ],
        );

        // --- Paiement produit à vérifier, montant insuffisant (Ahou → engrais) ---
        $engrais = ShopProduct::where('name', 'like', 'Engrais%')->first();
        $order = Order::updateOrCreate(
            ['user_id' => $ahou->id, 'orderable_type' => ShopProduct::class, 'orderable_id' => $engrais->id, 'status' => 'paiement'],
            [
                'customer_name' => $ahou->name,
                'item_label' => $engrais->name.' ×1',
                'quantity' => 1, 'amount' => $engrais->price,
                'ordered_at' => now()->subHours(4),
            ],
        );
        Payment::updateOrCreate(
            ['reference' => 'RDM-3925'],
            [
                'user_id' => $ahou->id,
                'payable_type' => ShopProduct::class, 'payable_id' => $engrais->id,
                'order_id' => $order->id,
                'label' => $engrais->name.' ×1',
                'amount' => $engrais->price, 'method' => 'orange_money', 'status' => 'a_verifier',
                'declared_amount' => 10000,
                'check_result' => ['proof' => 'ok', 'amount' => 'insufficient', 'gap' => 10000 - $engrais->price, 'declared' => 10000],
                'submitted_at' => now()->subHours(4),
            ],
        );

        // --- Commandes déjà traitées ---
        Order::updateOrCreate(
            ['user_id' => $kouassi->id, 'item_label' => 'Engrais organique ×2'],
            ['customer_name' => 'Kouassi Diby', 'quantity' => 2, 'amount' => 24000, 'status' => 'validee', 'ordered_at' => now()->subDay()],
        );
        Order::updateOrCreate(
            ['user_id' => $konan->id, 'item_label' => 'Boutures ×300'],
            ['customer_name' => 'Konan Ismaël', 'quantity' => 300, 'amount' => 150000, 'status' => 'expediee', 'ordered_at' => now()->subDays(5)],
        );
    }
}
