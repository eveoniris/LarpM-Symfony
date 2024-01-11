<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RightController extends AbstractController
{
    /**
     * Liste des droits.
     */
    #[Route('/rights/list', name: 'right.admin.list')]
    public function listAction(Request $request): Response
    {
        return $this->render(
            'right/list.twig',
            ['rights' => User::getAvailableRolesLabels()]
        );
    }
}
