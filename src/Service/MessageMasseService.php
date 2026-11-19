<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use App\Entity\User;

/**
 * Envoi d'un même message à plusieurs joueurs à partir d'une liste d'identifiants utilisateur ou personnage.
 * Un seul message par joueur, même quand plusieurs de ses personnages sont listés.
 */
final readonly class MessageMasseService
{
    public const string VARIABLE_PERSONNAGES = '{personnages}';

    public function __construct(
        private MailService $mailService,
    ) {
    }

    /**
     * @param list<int> $ids
     *
     * @return list<string> messages d'erreur, vide si la saisie est valide
     */
    public function valider(array $ids, string $titre, string $texte): array
    {
        $erreurs = [];
        if ([] === $ids) {
            $erreurs[] = 'Aucun identifiant valide dans la liste.';
        }
        if ('' === trim($titre)) {
            $erreurs[] = 'Le titre est obligatoire.';
        }
        if ('' === trim(strip_tags($texte))) {
            $erreurs[] = 'Le message est vide.';
        }

        return $erreurs;
    }

    /**
     * Remplace {personnages} par la liste « nom (n° id) » des personnages du joueur.
     */
    public function render(string $texte, MessageMasseDestinataire $destinataire): string
    {
        $items = array_map(static fn (Personnage $p): string => \sprintf('<li>%s (n° %d)</li>', htmlspecialchars($p->getNom(), \ENT_QUOTES), $p->getId()), $destinataire->personnages);

        return str_replace(self::VARIABLE_PERSONNAGES, [] === $items ? '' : '<ul>' . implode('', $items) . '</ul>', $texte);
    }

    /**
     * @param User|null $auteur null = message système « Équipe de gestion »
     *
     * @return int nombre de messages envoyés
     */
    public function send(MessageMasseCibles $cibles, string $titre, string $texte, ?User $auteur, bool $notify): int
    {
        foreach ($cibles->destinataires as $destinataire) {
            $corps = $this->render($texte, $destinataire);
            if (null === $auteur) {
                $this->mailService->newSystemMessage($destinataire->user, $corps, $titre, $notify);
                continue;
            }
            $this->mailService->newMessage($destinataire->user, $corps, $titre, $auteur, $notify);
        }

        return \count($cibles->destinataires);
    }
}
