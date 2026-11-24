<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\AnnonceRepository;
use App\Security\MultiRolesExpression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class IndexController extends AbstractController
{
    /**
     * Guide de style : réservé aux rôles qui construisent le contenu du site
     * (tout utilisateur connecté sans rôle en est exclu). ROLE_ADMIN hérite des autres.
     */
    #[Route('/design', name: 'design')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN, Role::SCENARISTE, Role::ORGA, Role::REGLE, Role::STOCK))]
    public function designAction(Request $request): Response
    {
        return $this->render('index/design.twig', ['user' => $this->getUser()]);
    }

    #[Route('/', name: 'homepage')]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'user' => $user,
            'allowRememberMe' => true,
            'participants' => $user?->getParticipants(),
            'annonces' => $annonceRepository->findBy([], ['id' => 'DESC']),
        ]);
    }
}
