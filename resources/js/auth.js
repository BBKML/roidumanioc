// Écrans d'authentification — bouton œil pour afficher / masquer le mot de passe.
function bindPasswordToggles() {
  document.querySelectorAll('[data-pw-toggle]').forEach((btn) => {
    if (btn.dataset.bound) return;
    btn.dataset.bound = '1';

    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('input');
      if (!input) return;
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      btn.classList.toggle('is-visible', reveal);
      btn.setAttribute('aria-label', reveal ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
      input.focus();
    });
  });
}

document.addEventListener('DOMContentLoaded', bindPasswordToggles);
