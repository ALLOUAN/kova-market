{{-- Preview of a newsletter campaign (CampaignActions::preview): the e-mail's own HTML in a sandboxed frame, so its
     styles neither leak into the back-office nor get its styles. --}}
<iframe
    title="Aperçu de l’e-mail"
    sandbox=""
    srcdoc="{{ $html }}"
    style="width: 100%; height: 70vh; border: 1px solid rgb(2 23 50 / 0.12); border-radius: 12px; background: #f4f6fa"
></iframe>
