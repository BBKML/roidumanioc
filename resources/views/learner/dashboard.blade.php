<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon espace</h2>
    </x-slot>

    <div class="py-10 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Mes formations</h3>
            @forelse ($inProgress as $enrollment)
                @php
                    $formation = $enrollment->formation;
                    $total = $formation->lessons->count();
                    $done = auth()->user()->lessonProgress()
                        ->whereIn('lesson_id', $formation->lessons->pluck('id'))->count();
                    $pct = $total ? round($done / $total * 100) : 0;
                @endphp
                <div class="border-b py-3">
                    <div class="flex justify-between text-sm">
                        <span class="font-bold">{{ $formation->title }}</span>
                        <span class="text-gray-500">{{ $done }}/{{ $total }} · {{ $pct }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded mt-2">
                        <div class="h-2 bg-green-600 rounded" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Vous ne suivez encore aucune formation.</p>
            @endforelse
        </div>

        @if ($pending->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold mb-2">Paiement en cours de vérification</h3>
                @foreach ($pending as $enrollment)
                    <div class="text-sm py-1">{{ $enrollment->formation->title }}</div>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-3">Prochains événements</h3>
            @foreach ($events as $event)
                <div class="text-sm py-1">{{ $event->title }} <span class="text-gray-500">· {{ $event->date_label }}</span></div>
            @endforeach
        </div>
    </div>
</x-app-layout>
