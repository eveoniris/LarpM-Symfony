<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Prévient les joueurs concernés par un ajout en masse (un message par joueur, tous ses personnages listés).
 */
final readonly class AjoutMasseNotifier
{
    public const string TEXTE_PAR_DEFAUT = "<p>Bonjour,</p><p>Votre {type} a fait l'objet d'un ajout ({valeur}) pour le motif suivant : {motif}.</p><p>{personnages}</p><p>Cordialement,<br />L'équipe de gestion.</p>";
    public const string TEXTE_POST_MORTEM = "<p>Bonjour,</p><p>Un gain d'XP post mortem ({valeur} XP, motif : {motif}) n'a pas été ajouté directement. Une mention a été enregistrée dans la biographie de scénarisation, il sera à attribuer au prochain personnage.</p><p>{personnages}</p><p>Cordialement,<br />L'équipe de gestion.</p>";

    public function __construct(
        private MessageMasseResolver $resolver,
        private MessageMasseService $messageService,
    ) {
    }

    /**
     * @param list<int> $personnageIds
     *
     * @return MessageMasseCibles destinataires trouvés, avec les personnages sans joueur
     */
    public function prevenir(AjoutMasseDemande $demande, string $texte, array $personnageIds): MessageMasseCibles
    {
        $cibles = $this->resolver->resolve(MessageMasseResolver::TYPE_PERSONNAGE, $personnageIds);
        $corps = str_replace(['{type}', '{valeur}', '{motif}'], [$demande->type->label(), (string) $demande->valeur(), htmlspecialchars($demande->motifPropre(), \ENT_QUOTES)], $texte);
        $this->messageService->send($cibles, 'Ajout : ' . $demande->type->label(), $corps, null, null !== $demande->message && $demande->message->mail);

        return $cibles;
    }
}
