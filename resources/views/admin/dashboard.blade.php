<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration — Vue d'ensemble</h2>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Membres actifs', $activeMembers],
                ['Formations publiées', $publishedFormations.' / '.$totalFormations],
                ['Paiements à vérifier', $paymentsToVerify->count()],
                ['Recettes encaissées', number_format($revenue, 0, ',', ' ').' FCFA'],
            ] as [$label, $value])
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $label }}</div>
                    <div class="text-2xl font-semibold mt-1">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Paiements à vérifier</h3>
            @forelse ($paymentsToVerify as $p)
                <div class="flex justify-between border-b py-3 text-sm">
                    <div>
                        <span class="font-bold">{{ $p->reference }}</span> — {{ $p->label }}
                        <div class="text-gray-500">{{ $p->user->name }} · {{ $p->method->label() }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold">{{ number_format($p->amount, 0, ',', ' ') }} FCFA</div>
                        <div class="text-xs {{ $p->isAmountConform() ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $p->isAmountConform() ? 'Montant conforme' : 'À contrôler' }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Aucun paiement en attente.</p>
            @endforelse
        </div>

        <p class="text-sm text-gray-400">
            Phase 1 — fondation. Les pages complètes (contenu du site, formations, paiements, membres…) arrivent en Phases 3 à 6.
        </p>
    </div>
</x-app-layout>
