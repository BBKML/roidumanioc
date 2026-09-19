{{-- Dialogue de confirmation maison (voir resources/js/admin.js, attribut data-confirm). --}}
<div id="confirmDialog" class="modal-back confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle">
  <div class="modal" style="max-width:440px">
    <div class="modal-head">
      <h3 id="confirmTitle">Confirmation</h3>
      <button type="button" data-role="cancel" aria-label="Annuler">&times;</button>
    </div>
    <div class="modal-body">
      <p data-role="message" style="margin:0;font-size:.92rem"></p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn ghost" data-role="cancel">Annuler</button>
      <button type="button" class="btn" data-role="ok">Confirmer</button>
    </div>
  </div>
</div>
