<?php

namespace App\Controller;

use _PHPStan_c875e8309\Symfony\Component\Finder\Exception\AccessDeniedException;
use App\Entity\EtatCivil;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EtatCivilController extends AbstractController
{
    /**
     * Affiche l'état civil de l'utilisateur.
     */
    #[Route('/etat-civil/{id}/detail', name: 'etatCivil.detail')]
    #[IsGranted(Role::USER->value)]
    public function detailAction(#[MapEntity] EtatCivil $etatCivil): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $user->getEtatCivil()?->getId() !== $etatCivil->getId()) {
            throw new AccessDeniedException();
        }

        return $this->render(
            'etatCivil/detail.twig',
            ['etatCivil' => $etatCivil]
        );
    }
}
