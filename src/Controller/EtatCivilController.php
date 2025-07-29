<?php

namespace App\Controller;

use App\Entity\EtatCivil;
use App\Entity\User;
use App\Enum\Role;
use App\Form\EtatCivilForm;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EtatCivilController extends AbstractController
{
    /**
     * Affiche l'état civil de l'utilisateur.
     */
    #[Route('/etat-civil/{etatCivil}/detail', name: 'etatCivil.detail')]
    #[IsGranted(Role::USER->value)]
    public function detailAction(#[MapEntity] EtatCivil $etatCivil): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || ($user->getEtatCivil()?->getId() !== $etatCivil->getId() && !$this->isGranted(
                    Role::ORGA->value,
                ))) {
            throw new AccessDeniedException();
        }

        return $this->render(
            'etatCivil/detail.twig',
            ['etatCivil' => $etatCivil],
        );
    }

    #[Route('/etat-civil/{etatCivil}/update', name: 'etatCivil.update')]
    #[IsGranted(Role::USER->value)]
    public function updateAction(#[MapEntity] EtatCivil $etatCivil, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || ($user->getEtatCivil()?->getId() !== $etatCivil->getId() && !$this->isGranted(
                    Role::ORGA->value,
                ))) {
            throw new AccessDeniedException();
        }

        return $this->handleCreateOrUpdate(
            $request,
            $etatCivil,
            EtatCivilForm::class,
            routes: ['root' => 'etatCivil.', 'list' => false],
        );
    }
}
