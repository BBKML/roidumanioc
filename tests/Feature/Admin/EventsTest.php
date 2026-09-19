<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Events;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'actif']));
    }

    public function test_admin_can_create_an_event_with_an_image(): void
    {
        Storage::fake('public');

        Livewire::test(Events::class)
            ->call('new')
            ->set('title', 'Journée portes ouvertes')
            ->set('date_label', '12 sept. 2026')
            ->set('type', 'Visite')
            ->set('image', UploadedFile::fake()->image('affiche.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('title', 'Journée portes ouvertes')->firstOrFail();
        $this->assertNotNull($event->image_path);
        Storage::disk('public')->assertExists($event->image_path);
    }

    public function test_admin_can_replace_an_event_image_and_the_old_one_is_deleted(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('events', 'public');
        $event = Event::create([
            'title' => 'Atelier transformation',
            'type' => 'Atelier',
            'status' => 'planifie',
            'image_path' => $oldPath,
        ]);

        Livewire::test(Events::class)
            ->call('edit', $event->id)
            ->set('image', UploadedFile::fake()->image('new.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertNotEquals($oldPath, $event->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($event->image_path);
    }

    public function test_editing_an_event_without_choosing_a_new_image_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('events', 'public');
        $event = Event::create([
            'title' => 'Foire régionale',
            'type' => 'Visite',
            'status' => 'planifie',
            'image_path' => $path,
        ]);

        Livewire::test(Events::class)
            ->call('edit', $event->id)
            ->set('title', 'Foire régionale (mise à jour)')
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertSame($path, $event->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_deleting_an_event_removes_its_image_from_disk(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('gone.jpg')->store('events', 'public');
        $event = Event::create([
            'title' => 'Événement à supprimer',
            'type' => 'Live',
            'status' => 'planifie',
            'image_path' => $path,
        ]);

        Livewire::test(Events::class)->call('delete', $event->id);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
