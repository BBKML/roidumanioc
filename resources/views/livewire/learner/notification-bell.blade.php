<div class="notif-bell" x-data="{ open: false }" wire:poll.30s>
    <button type="button" class="chip notif-trigger" @click="open = !open" aria-label="Notifications">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6z"/>
            <path d="M10 21a2 2 0 0 0 4 0"/>
        </svg>
        @if ($unreadCount > 0)
            <span class="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div class="notif-dropdown" x-show="open" x-cloak @click.outside="open = false" x-transition>
        <div class="notif-head">
            <b>Notifications</b>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="notif-mark-all">Tout marquer comme lu</button>
            @endif
        </div>

        <div class="notif-list">
            @forelse ($recent as $n)
                <a
                    href="{{ $n->data['url'] ?? '#' }}"
                    wire:navigate
                    wire:click="markAsRead('{{ $n->id }}')"
                    class="notif-item {{ $n->read_at ? '' : 'unread' }}"
                >
                    <span class="notif-title">{{ $n->data['title'] ?? '' }}</span>
                    <span class="notif-msg">{{ $n->data['message'] ?? '' }}</span>
                    <span class="notif-time">{{ $n->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <div class="notif-empty">Aucune notification pour le moment.</div>
            @endforelse
        </div>
    </div>
</div>
