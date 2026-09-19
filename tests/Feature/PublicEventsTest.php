<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_events_page_shows_an_explicit_empty_state_when_none_are_planned(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('Aucun événement à venir pour le moment.');
    }

    public function test_events_page_lists_planned_events_but_not_finished_ones(): void
    {
        Event::create([
            'title' => 'Journée portes ouvertes',
            'type' => 'Visite',
            'status' => 'planifie',
            'starts_at' => now()->addWeek(),
        ]);
        Event::create([
            'title' => 'Ancien atelier',
            'type' => 'Atelier',
            'status' => 'termine',
            'starts_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('events.index'));

        $response->assertOk()
            ->assertSee('Journée portes ouvertes')
            ->assertDontSee('Ancien atelier');
    }

    public function test_a_future_event_renders_a_countdown_deadline(): void
    {
        $event = Event::create([
            'title' => 'Formation en présentiel',
            'type' => 'Atelier',
            'status' => 'planifie',
            'starts_at' => now()->addDays(3),
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('data-event-deadline="'.$event->starts_at->toIso8601String().'"', false);
    }

    public function test_an_event_without_a_precise_datetime_shows_no_countdown(): void
    {
        Event::create([
            'title' => 'Rencontre annuelle',
            'type' => 'Live',
            'status' => 'planifie',
            'date_label' => 'Courant octobre 2026',
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('Rencontre annuelle')
            ->assertSee('Courant octobre 2026')
            ->assertDontSee('data-event-deadline', false);
    }
}
