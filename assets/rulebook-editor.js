// Éditeur markdown du rulebook (EasyMDE), chargé uniquement sur les pages d'édition.
// Markdown pur : pas de WYSIWYG, pas de HTML saisi. L'aperçu vient du serveur (même rendu sûr que la page de lecture).
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

function initialiser() {
    document.querySelectorAll('textarea.js-markdown').forEach((zone) => {
        if (zone.dataset.mdeInit === '1') {
            return;
        }
        zone.dataset.mdeInit = '1';

        const urlApercu = zone.dataset.apercuUrl;
        const jeton = zone.dataset.apercuToken;

        new EasyMDE({
            element: zone,
            forceSync: true,
            autoDownloadFontAwesome: false,
            spellChecker: false,
            status: false,
            autofocus: false,
            minHeight: '180px',
            toolbar: ['bold', 'italic', 'heading-2', '|', 'unordered-list', 'ordered-list', 'table', 'link', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide'],
            previewRender: (texte, cible) => {
                fetch(urlApercu, {
                    method: 'POST',
                    headers: {'X-CSRF-Token': jeton, 'Content-Type': 'text/plain; charset=utf-8'},
                    body: texte,
                    credentials: 'same-origin',
                })
                    .then((reponse) => (reponse.ok ? reponse.text() : 'Aperçu indisponible.'))
                    .then((html) => {
                        cible.innerHTML = html;
                    })
                    .catch(() => {
                        cible.textContent = 'Aperçu indisponible.';
                    });

                return 'Chargement de l\'aperçu...';
            },
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiser);
} else {
    initialiser();
}
