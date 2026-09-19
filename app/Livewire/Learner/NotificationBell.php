<?php

namespace App\Livewire\Learner;

use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Cloche de notifications de l'espace apprenant (§24, Phase 9) — même pattern de
 * compteur quasi-live que App\Livewire\Admin\Nav (wire:poll, pas de websocket).
 * Consomme le système natif Illuminate\Notifications (DatabaseNotification) déjà
 * disponible via `Notifiable` sur User.
 */
class NotificationBell extends Component
{
    /** Les 10 plus récentes — une cloche n'est pas un centre de notifications complet. */
    private const RECENT_LIMIT = 10;

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->whereKey($notificationId)->first();

        $notification?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $user = auth()->user();

        /** @var Collection $recent */
        $recent = $user->notifications()->latest()->limit(self::RECENT_LIMIT)->get();

        return view('livewire.learner.notification-bell', [
            'recent' => $recent,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
