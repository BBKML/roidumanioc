<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Formulaires d'inscription</h2>
      <p>Créez une page d'inscription publique par campagne (formation, session…) et partagez son
        lien en bio ou sous vos vidéos TikTok/Facebook. Le contenu est entièrement modifiable ;
        les inscrits arrivent ensuite dans <a href="{{ route('admin.registration-leads') }}" wire:navigate>Prospects</a>.</p>
    </div>
    <a class="btn" href="{{ route('admin.registration-forms.create') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouveau formulaire
    </a>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Formulaire</th><th>Lien public</th><th>Prospects</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($forms as $form)
          <tr class="row" wire:key="form-{{ $form->id }}">
            <td>
              <a class="cell-main" href="{{ route('admin.registration-forms.edit', $form->slug) }}" wire:navigate style="color:inherit;text-decoration:none">{{ $form->title }}</a>
            </td>
            <td>
              <div class="row-actions" style="justify-content:flex-start">
                <code style="font-size:.78rem;color:var(--ink-soft,#586457)">/inscription/{{ $form->slug }}</code>
                <button type="button" class="iact" title="Copier le lien" data-copy="{{ $form->publicUrl() }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>
                </button>
                @if ($form->isPublished())
                  <a class="iact" href="{{ $form->publicUrl() }}" title="Ouvrir la page publique">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
                  </a>
                @endif
              </div>
            </td>
            <td class="nums">{{ $form->leads_count }}</td>
            <td>
              <button wire:click="togglePublish({{ $form->id }})" title="Basculer brouillon / publié" style="border:none;background:none;padding:0">
                <x-adm.pill :status="$form->status" style="cursor:pointer" />
              </button>
            </td>
            <td>
              <div class="icon-actions">
                <a class="iact" href="{{ route('admin.registration-forms.edit', $form->slug) }}" wire:navigate title="Modifier">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
                </a>
                <button class="iact danger" wire:click="delete({{ $form->id }})" data-confirm="Supprimer ce formulaire ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty"><p>Aucun formulaire. Créez le premier.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
