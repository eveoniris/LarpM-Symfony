import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

/*
 * Confirmation par modale Bootstrap avant de soumettre un formulaire de choix
 * (achat de compétence, choix de religion).
 *
 * - le <select> du formulaire reste le champ réel (repli sans JS, CSRF inchangé) ;
 * - un bouton data-action="confirm-choice#ask" avec data-confirm-choice-value-param
 *   sélectionne la valeur dans le <select> cible puis ouvre la modale ;
 * - le message est un gabarit "{cle}" rempli avec les data-* des <option> sélectionnées.
 */
export default class extends Controller {
    static targets = ['form', 'select', 'modal', 'message'];
    static values = {message: String};

    connect() {
        this.confirmed = false;
    }

    // Bouton « ajouter / choisir » d'une carte : positionne la valeur du select cible.
    ask(event) {
        event.preventDefault();

        const {value, target} = event.params;
        const select = this.selectTargets.find((element) => element.dataset.confirmChoiceName === (target ?? 'main'));
        if (select) {
            select.value = String(value);
        }

        this.open();
    }

    // Soumission directe du formulaire : on intercepte pour demander confirmation.
    submit(event) {
        if (this.confirmed) {
            return;
        }

        event.preventDefault();
        this.open();
    }

    confirm() {
        this.confirmed = true;
        Modal.getOrCreateInstance(this.modalTarget).hide();
        this.formTarget.requestSubmit();
    }

    open() {
        // laisse la validation native signaler un champ requis encore vide
        if (!this.formTarget.reportValidity()) {
            return;
        }

        this.messageTarget.textContent = this.buildMessage();
        Modal.getOrCreateInstance(this.modalTarget).show();
    }

    buildMessage() {
        const data = {};
        this.selectTargets.forEach((select) => {
            const option = select.selectedOptions[0];
            if (option) {
                Object.assign(data, option.dataset);
            }
        });

        return this.messageValue.replace(/\{(\w+)\}/g, (match, key) => data[key] ?? '');
    }
}
