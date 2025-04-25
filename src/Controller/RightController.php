<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Security\MultiRolesExpression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RightController extends AbstractController
{
    /**
     * Liste des droits.
     */
    #[Route('/rights/list', name: 'right.list')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN))]
    public function listAction(Request $request): Response
    {
        return $this->render(
            'right/list.twig',
            ['rights' => User::getAvailableRolesLabels()],
        );
    }
}
