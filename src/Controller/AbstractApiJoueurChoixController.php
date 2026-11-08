<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\PersonnageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Socle des routes joueur qui consomment un choix en attente (trigger) : propriétaire, groupe non verrouillé, trigger présent.
 */
abstract class AbstractApiJoueurChoixController extends AbstractController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<TriggerType> $tags un seul de ces choix en attente suffit
     */
    protected function guardChoix(int $id, array $tags, PersonnageRepository $personnages): Personnage|JsonResponse
    {
        $personnage = $personnages->find($id);
        if (null === $personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || $personnage->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        if (true === $personnage->getLastParticipantGnGroupe()?->getLock()) {
            return $this->json(['error' => 'Le groupe est verrouillé : il n\'est plus possible de modifier ce personnage.', 'code' => 'groupe_verrouille'], 403);
        }

        foreach ($tags as $tag) {
            if ($personnage->hasTrigger($tag)) {
                return $personnage;
            }
        }

        return $this->json(['error' => 'Aucun choix en attente de ce type.', 'code' => 'aucun_choix'], 403);
    }

    /** Lit un identifiant entier dans le corps JSON, ou renvoie la réponse 422. */
    protected function idDuCorps(Request $request, string $cle): int|JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $valeur = \is_array($payload) ? $payload[$cle] ?? null : null;

        return \is_int($valeur) ? $valeur : $this->json(['error' => $cle . ' (entier) requis'], 422);
    }

    /** Premier choix en attente parmi les tags donnés. */
    protected function premierTrigger(Personnage $personnage, TriggerType ...$tags): ?\App\Entity\PersonnageTrigger
    {
        foreach ($tags as $tag) {
            $trigger = $personnage->getTrigger($tag);
            if (null !== $trigger) {
                return $trigger;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    protected function journaliser(LogActionType $type, array $data): void
    {
        $logAction = new LogAction();
        $logAction->setDate(new DateTime());
        $logAction->setUser($this->getUser());
        $logAction->setType($type);
        $logAction->setData($data);
        $this->entityManager->persist($logAction);
    }
}
