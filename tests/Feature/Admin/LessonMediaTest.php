<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\LessonManager;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LessonMediaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Formation $formation;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->formation = Formation::create([
            'title' => 'Culture avancée', 'category' => 'Culture', 'price' => 15000, 'status' => 'publiee',
        ]);
    }

    private function lw()
    {
        return Livewire::actingAs($this->admin)->test(LessonManager::class, ['formation' => $this->formation]);
    }

    public function test_admin_adds_a_lesson_with_a_youtube_link(): void
    {
        $this->lw()
            ->call('new')
            ->set('title', 'Intro')
            ->set('type', 'video')
            ->set('videoProvider', 'link')
            ->set('videoUrl', 'https://youtu.be/aqz-KE-bpKQ')
            ->call('save')
            ->assertHasNoErrors();

        $lesson = $this->formation->lessons()->firstOrFail();
        $this->assertSame('link', $lesson->video_provider);
        $this->assertSame('embed', $lesson->mediaKind());
        $this->assertStringContainsString('youtube-nocookie.com/embed/aqz-KE-bpKQ', $lesson->embedUrl());
    }

    public function test_admin_uploads_a_video_stored_privately(): void
    {
        $this->lw()
            ->call('new')
            ->set('title', 'Vidéo cours')
            ->set('type', 'video')
            ->set('videoProvider', 'upload')
            ->set('videoUpload', UploadedFile::fake()->create('cours.mp4', 2048, 'video/mp4'))
            ->call('save')
            ->assertHasNoErrors();

        $lesson = $this->formation->lessons()->firstOrFail();
        $this->assertSame('upload', $lesson->video_provider);
        $this->assertSame('local', $lesson->video_disk);
        Storage::disk('local')->assertExists($lesson->video_path);
        $this->assertSame('file', $lesson->mediaKind());
        $this->assertSame(route('lessons.video', $lesson), $lesson->videoStreamUrl());
    }

    public function test_uploaded_video_rejects_non_video_files(): void
    {
        $this->lw()
            ->call('new')
            ->set('title', 'X')
            ->set('videoProvider', 'upload')
            ->set('videoUpload', UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'))
            ->call('save')
            ->assertHasErrors('videoUpload');
    }

    public function test_video_stream_is_gated_by_enrolment(): void
    {
        $lesson = $this->formation->lessons()->create([
            'title' => 'V', 'type' => 'video', 'video_provider' => 'upload',
            'video_disk' => 'local', 'video_path' => 'lesson-videos/x.mp4', 'position' => 0,
        ]);
        Storage::disk('local')->put('lesson-videos/x.mp4', 'fake-bytes');

        $stranger = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $enrolled = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $this->formation->enrollments()->create(['user_id' => $enrolled->id, 'status' => 'validee', 'enrolled_at' => now()]);

        $url = route('lessons.video', $lesson);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($stranger)->get($url)->assertForbidden();
        $this->actingAs($enrolled)->get($url)->assertOk();
        $this->actingAs($this->admin)->get($url)->assertOk();
    }

    public function test_admin_attaches_a_pdf_and_it_is_gated(): void
    {
        $lesson = $this->formation->lessons()->create(['title' => 'L', 'type' => 'video', 'position' => 0]);

        $this->lw()
            ->call('edit', $lesson->id)
            ->set('attachmentTitle', 'Fiche plantation')
            ->set('attachmentFile', UploadedFile::fake()->create('fiche.pdf', 120, 'application/pdf'))
            ->call('addAttachment')
            ->assertHasNoErrors();

        $attachment = $lesson->attachments()->firstOrFail();
        $this->assertTrue($attachment->isPdf());
        Storage::disk('local')->assertExists($attachment->path);

        $stranger = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $this->actingAs($stranger)->get(route('lessons.attachment', $attachment))->assertForbidden();
        $this->actingAs($this->admin)->get(route('lessons.attachment', $attachment))->assertOk();
    }

    public function test_deleting_a_lesson_removes_its_files(): void
    {
        $lesson = $this->formation->lessons()->create([
            'title' => 'L', 'type' => 'video', 'video_provider' => 'upload',
            'video_disk' => 'local', 'video_path' => 'lesson-videos/y.mp4', 'position' => 0,
        ]);
        Storage::disk('local')->put('lesson-videos/y.mp4', 'x');
        $att = $lesson->attachments()->create(['title' => 'A', 'disk' => 'local', 'path' => 'lesson-files/a.pdf', 'mime' => 'application/pdf', 'size' => 1]);
        Storage::disk('local')->put('lesson-files/a.pdf', 'x');

        Livewire::actingAs($this->admin)->test(LessonManager::class, ['formation' => $this->formation])
            ->call('delete', $lesson->id);

        Storage::disk('local')->assertMissing('lesson-videos/y.mp4');
        Storage::disk('local')->assertMissing('lesson-files/a.pdf');
        $this->assertModelMissing($lesson);
    }

    public function test_attachments_are_optional_and_title_falls_back_to_filename(): void
    {
        $lesson = $this->formation->lessons()->create(['title' => 'L', 'type' => 'video', 'position' => 0]);

        // Sans fichier : pas d'erreur, rien n'est créé.
        $this->lw()->call('edit', $lesson->id)->call('addAttachment')->assertHasNoErrors();
        $this->assertSame(0, $lesson->attachments()->count());

        // Fichier sans intitulé : l'intitulé est repris du nom du fichier.
        $this->lw()
            ->call('edit', $lesson->id)
            ->set('attachmentFile', UploadedFile::fake()->create('Fiche technique.pdf', 50, 'application/pdf'))
            ->call('addAttachment')
            ->assertHasNoErrors();

        $this->assertSame('Fiche technique', $lesson->attachments()->first()->title);
    }

    public function test_document_lesson_uses_its_pdf_as_main_content(): void
    {
        $lesson = $this->formation->lessons()->create(['title' => 'Guide PDF', 'type' => 'document', 'position' => 0]);
        $lesson->attachments()->create(['title' => 'Guide', 'disk' => 'local', 'path' => 'lesson-files/g.pdf', 'mime' => 'application/pdf', 'size' => 1]);

        $lesson->load('attachments');
        $this->assertSame('document', $lesson->mediaKind());
        $this->assertNotNull($lesson->primaryDocument());
    }
}
