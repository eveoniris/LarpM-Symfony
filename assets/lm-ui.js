/*
 * Comportements communs de l'interface (délégation d'événements, liés une seule fois) :
 * - « Lire la suite » : [data-gs-toggle="#id"] bascule .is-open sur la cible (partiel groupeSecondaire/fragment/clamp.twig) ;
 *   avec [data-lm-clamp-auto], le lien n'est conservé que si le texte déborde réellement.
 * - Copie dans le presse-papiers : [data-gs-copy="#id"] (listes d'e-mails des groupes transverses).
 * - Photo en grand : a[data-lm-photo] ouvre l'image dans une fenêtre modale (<dialog>), le lien garde son
 *   comportement normal avec Ctrl, Cmd, Maj ou un clic autre que le principal.
 */
let photoDialog = null;

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

    if (document.readyState === 'complete') {
        checkAutoClamps();
    } else {
        window.addEventListener('load', checkAutoClamps);
    }
}
