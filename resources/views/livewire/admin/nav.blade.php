@php
    $icons = [
        'grid' => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.3"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'book' => '<path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/><path d="M11 6v14"/>',
        'spark' => '<path d="M12 3v6M12 15v6M3 12h6M15 12h6"/>',
        'box' => '<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'chat' => '<path d="M4 5h16v11H9l-4 4V5z"/>',
        'link' => '<path d="M9 15 15 9"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c.7-3.4 3-5 5.5-5s4.8 1.6 5.5 5"/><path d="M16 5.5a3 3 0 0 1 0 5.6M17.5 20c-.3-2-1-3.5-2-4.5"/>',
        'gear' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M4 12H1M23 12h-3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'flag' => '<path d="M4 22V3"/><path d="M4 4h13l-2.5 4L17 12H4"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="M9 14l-2 7 5-3 5 3-2-7"/>',
        'basket' => '<path d="M4 9h16l-1.5 10a2 2 0 0 1-2 1.8H7.5a2 2 0 0 1-2-1.8L4 9z"/><path d="M8 9V7a4 4 0 0 1 8 0v2M9 13v3M15 13v3"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r=".5" fill="currentColor"/>',
        'leaf' => '<path d="M4 20c8 0 16-8 16-16-8 0-16 8-16 16z"/><path d="M4 20c2-6 6-10 12-12"/>',
        'truck' => '<path d="M1 3h13v13H1z"/><path d="M14 8h4l3 3v5h-7V8z"/><circle cx="6.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>',
    ];
    $svg = fn ($k) => '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.($icons[$k] ?? '').'</svg>';
@endphp
<nav id="nav" wire:poll.30s>
  @foreach ($nav as $group => $items)
    <div class="nav-label">{{ $group }}</div>
    @foreach ($items as [$route, $label, $icon])
      <a class="nav-item {{ $current === $route ? 'on' : '' }}" href="{{ route($route) }}" wire:navigate>
        {!! $svg($icon) !!}<span>{{ $label }}</span>
        @if (($badges[$route] ?? 0) > 0)<span class="tag">{{ $badges[$route] }}</span>@endif
      </a>
    @endforeach
  @endforeach
</nav>
