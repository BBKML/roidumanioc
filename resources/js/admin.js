// Back-office — interactions minimales (le reste est géré par Livewire).

function bindSidebar() {
  const sidebar = document.getElementById('sidebar');
  const burger = document.getElementById('burger');
  const backdrop = document.getElementById('drawerBack');
  if (!sidebar || !burger || burger.dataset.bound) return;
  burger.dataset.bound = '1';

  const close = () => {
    sidebar.classList.remove('open');
    backdrop?.classList.remove('open');
  };
  burger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    backdrop?.classList.toggle('open');
  });
  backdrop?.addEventListener('click', close);
  sidebar.addEventListener('click', (e) => {
    if (e.target.closest('a[href], .nav-item')) close();
  });
}

function bindFlash() {
  document.querySelectorAll('.lw-flash').forEach((el) => {
    setTimeout(() => el.remove(), 3200);
  });
}

function toast(message) {
  if (!message) return;
  const el = document.createElement('div');
  el.className = 'lw-flash';
  el.setAttribute('role', 'status');
  el.setAttribute('aria-live', 'polite');
  el.textContent = message;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

/* -------------------------------------------------------------------------
 |  Dialogue de confirmation maison — remplace window.confirm() de Livewire.
 |  Utilisation dans les vues : data-confirm="Message ?" [data-confirm-label]
 |                              [data-confirm-danger] sur un <button wire:click>.
 * ---------------------------------------------------------------------- */
let confirmBound = false;
function bindConfirmDialog() {
  if (confirmBound) return;
  confirmBound = true;

  document.addEventListener(
    'click',
    (e) => {
      const trigger = e.target.closest('[data-confirm]');
      if (!trigger) return;

      e.preventDefault();
      e.stopImmediatePropagation();

      const message = trigger.getAttribute('data-confirm');
      const label = trigger.getAttribute('data-confirm-label') || 'Confirmer';
      const danger = trigger.hasAttribute('data-confirm-danger') || trigger.classList.contains('danger');

      const dialog = document.getElementById('confirmDialog');
      if (!dialog) {
        if (window.confirm(message)) fireThrough(trigger);
        return;
      }

      dialog.querySelector('[data-role=message]').textContent = message;
      const ok = dialog.querySelector('[data-role=ok]');
      ok.textContent = label;
      ok.classList.toggle('is-danger', danger);
      dialog.classList.add('open');
      ok.focus();

      const cleanup = () => {
        dialog.classList.remove('open');
        ok.removeEventListener('click', onOk);
        dialog.querySelectorAll('[data-role=cancel]').forEach((b) => b.removeEventListener('click', cleanup));
        dialog.removeEventListener('mousedown', onBackdrop);
        document.removeEventListener('keydown', onKey);
      };
      const onOk = () => { cleanup(); fireThrough(trigger); };
      const onBackdrop = (ev) => { if (ev.target === dialog) cleanup(); };
      const onKey = (ev) => { if (ev.key === 'Escape') cleanup(); };

      ok.addEventListener('click', onOk);
      dialog.querySelectorAll('[data-role=cancel]').forEach((b) => b.addEventListener('click', cleanup));
      dialog.addEventListener('mousedown', onBackdrop);
      document.addEventListener('keydown', onKey);
    },
    true, // capture : on passe avant le gestionnaire de Livewire
  );
}

// Rejoue le clic sans le garde-fou -> wire:click / submit s'exécutent normalement.
function fireThrough(el) {
  const message = el.getAttribute('data-confirm');
  el.removeAttribute('data-confirm');
  el.click();
  if (el.isConnected) el.setAttribute('data-confirm', message);
}

/* -------------------------------------------------------------------------
 |  Copier un lien dans le presse-papier — data-copy="valeur" sur un bouton.
 * ---------------------------------------------------------------------- */
let copyBound = false;
function bindCopyLink() {
  if (copyBound) return;
  copyBound = true;

  document.addEventListener('click', async (e) => {
    const trigger = e.target.closest('[data-copy]');
    if (!trigger) return;

    const value = trigger.getAttribute('data-copy');
    try {
      await navigator.clipboard.writeText(value);
      toast('Lien copié !');
    } catch {
      toast("Impossible de copier automatiquement — copiez le lien manuellement.");
    }
  });
}

function boot() {
  bindSidebar();
  bindFlash();
  bindConfirmDialog();
  bindCopyLink();
}

// wire:navigate (utilisé sur tous les liens de menu admin/apprenant) essaie de remonter en
// haut de la nouvelle page via document.body.scrollTo() — mais `html,body{height:100%}`
// (admin.css) fait que c'est <html>, pas <body>, qui défile réellement ici : cet appel ne
// bouge donc rien à l'écran, et l'utilisateur reste à la position de scroll de l'ancienne
// page. On force nous-mêmes le retour en haut après chaque navigation.
function resetScrollAfterNavigate() {
  window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
document.addEventListener('livewire:navigated', resetScrollAfterNavigate);
document.addEventListener('livewire:init', () => {
  window.Livewire.hook('morph.updated', bindFlash);
  window.Livewire.on('notify', (payload) => {
    toast(Array.isArray(payload) ? payload[0]?.message : payload?.message);
  });
});
