/*
 * Comportements communs de l'interface (délégation d'événements, liés une seule fois) :
 * - « Lire la suite » : [data-gs-toggle="#id"] bascule .is-open sur la cible (partiel groupeSecondaire/fragment/clamp.twig) ;
 *   avec [data-lm-clamp-auto], le lien n'est conservé que si le texte déborde réellement.
 * - Copie dans le presse-papiers : [data-gs-copy="#id"] (listes d'e-mails des groupes transverses).
 * - Photo en grand : a[data-lm-photo] ouvre l'image dans une fenêtre modale (<dialog>), le lien garde son
 *   comportement normal avec Ctrl, Cmd, Maj ou un clic autre que le principal.
 * - Confirmation d'une action : [data-lm-confirm="message"] sur un formulaire ou sur son bouton d'envoi ouvre une fenêtre
 *   modale (<dialog>) à la place du confirm() du navigateur ; [data-lm-confirm-label] change le texte du bouton de
 *   confirmation et [data-lm-confirm-danger] le colore en rouge. Sans <dialog>, repli sur confirm().
 * - Tableaux de liste en interface bêta : voir lm-lists.js.
 * - Menus déroulants dans un tableau défilant (.table-responsive, .lm-table-wrap) : position fixe, sinon le conteneur
 *   (overflow) les rogne, surtout quand le tableau n'a qu'une ligne.
 */
import { Dropdown } from 'bootstrap';
import './lm-lists.js';

let photoDialog = null;
let confirmDialog = null;

/** Renvoie un formulaire déjà confirmé, avec son bouton d'envoi d'origine (donc son éventuel formaction). */
function resubmit(form, submitter) {
    form.dataset.lmConfirmed = '1';
    form.requestSubmit(submitter || undefined);
    delete form.dataset.lmConfirmed;
}

/**
 * Demande confirmation dans une fenêtre modale puis renvoie le formulaire.
 */
function askConfirmation(form, submitter) {
    const source = submitter && submitter.dataset.lmConfirm !== undefined ? submitter : form;
    const message = source.dataset.lmConfirm;
    if (typeof HTMLDialogElement === 'undefined') {
        if (window.confirm(message)) {
            resubmit(form, submitter);
        }
        return;
    }
    if (!confirmDialog) {
        confirmDialog = document.createElement('dialog');
        confirmDialog.className = 'lm-confirm-dialog';
        confirmDialog.setAttribute('aria-labelledby', 'lm-confirm-message');
        confirmDialog.innerHTML = '<form method="dialog"><p id="lm-confirm-message" class="lm-confirm-message"></p>'
            + '<div class="lm-confirm-actions"><button type="submit" value="cancel" class="btn btn-secondary lm-confirm-cancel">Annuler</button>'
            + '<button type="submit" value="ok" class="btn lm-confirm-ok"></button></div></form>';
        document.body.appendChild(confirmDialog);
    }
    confirmDialog.querySelector('.lm-confirm-message').textContent = message;
    const ok = confirmDialog.querySelector('.lm-confirm-ok');
    ok.textContent = source.dataset.lmConfirmLabel || 'Confirmer';
    ok.className = 'btn lm-confirm-ok ' + (source.dataset.lmConfirmDanger !== undefined ? 'btn-danger' : 'btn-primary');
    confirmDialog.returnValue = '';
    confirmDialog.onclose = () => {
        if (confirmDialog.returnValue === 'ok') {
            resubmit(form, submitter);
        }
    };
    confirmDialog.showModal();
    confirmDialog.querySelector('.lm-confirm-cancel').focus();
}

function openPhoto(link) {
    if (!photoDialog) {
        photoDialog = document.createElement('dialog');
        photoDialog.className = 'lm-photo-dialog';
        photoDialog.innerHTML = '<form method="dialog"><button class="lm-photo-close" aria-label="Fermer">&times;</button></form><img alt=""><p class="lm-photo-caption"></p>';
        photoDialog.addEventListener('click', (event) => {
            if (event.target === photoDialog) {
                photoDialog.close();
            }
        });
        document.body.appendChild(photoDialog);
    }
    const image = photoDialog.querySelector('img');
    const caption = link.dataset.lmPhoto || link.getAttribute('aria-label') || '';
    image.src = link.getAttribute('href');
    image.alt = caption;
    photoDialog.querySelector('.lm-photo-caption').textContent = caption;
    photoDialog.showModal();
}

function toggleReadMore(toggle) {
    const target = document.querySelector(toggle.dataset.gsToggle);
    if (!target) {
        return;
    }
    const open = target.classList.toggle('is-open');
    toggle.textContent = open ? 'Réduire' : 'Lire la suite';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
}

function copyToClipboard(button) {
    const source = document.querySelector(button.dataset.gsCopy);
    if (!source || !navigator.clipboard) {
        return;
    }
    navigator.clipboard.writeText(source.textContent.trim());
    const label = button.querySelector('.gs-copy-label');
    if (label) {
        const previous = label.textContent;
        label.textContent = 'Copié';
        setTimeout(() => { label.textContent = previous; }, 2000);
    }
}

/** Retire « Lire la suite » des textes qui tiennent dans leur cadre (les textes masqués sont ignorés). */
function checkAutoClamps() {
    document.querySelectorAll('.gs-clamp[data-lm-clamp-auto]:not(.is-open)').forEach((box) => {
        if (box.offsetParent === null || box.scrollHeight > box.clientHeight + 2) {
            return;
        }
        box.classList.add('is-open');
        const link = box.nextElementSibling && box.nextElementSibling.querySelector('[data-gs-toggle]');
        if (link) {
            link.parentElement.remove();
        }
    });
}

/** Les menus des tableaux défilants sortent du conteneur en position fixe (Popper strategy: fixed). */
function initTableDropdowns() {
    document.querySelectorAll('.table-responsive [data-bs-toggle="dropdown"], .lm-table-wrap [data-bs-toggle="dropdown"]').forEach((toggle) => {
        Dropdown.getOrCreateInstance(toggle, {popperConfig: (defaultConfig) => ({...defaultConfig, strategy: 'fixed'})});
    });
}

if (!window.lmUiBound) {
    window.lmUiBound = true;

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-gs-toggle]');
        if (toggle) {
            event.preventDefault();
            toggleReadMore(toggle);
            return;
        }
        const copy = event.target.closest('[data-gs-copy]');
        if (copy) {
            copyToClipboard(copy);
            return;
        }
        const photo = event.target.closest('a[data-lm-photo]');
        if (photo && !event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0 && typeof HTMLDialogElement !== 'undefined') {
            event.preventDefault();
            openPhoto(photo);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.lmConfirmed === '1') {
            return;
        }
        const submitter = event.submitter;
        if (form.dataset.lmConfirm === undefined && !(submitter && submitter.dataset.lmConfirm !== undefined)) {
            return;
        }
        event.preventDefault();
        askConfirmation(form, submitter);
    });

    if (document.readyState === 'complete') {
        checkAutoClamps();
    } else {
        window.addEventListener('load', checkAutoClamps);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTableDropdowns);
    } else {
        initTableDropdowns();
    }
    document.addEventListener('turbo:load', initTableDropdowns);
}
