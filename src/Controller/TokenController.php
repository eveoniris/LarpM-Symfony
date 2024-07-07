<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenForm;
use App\Repository\TokenRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/token', name: 'token.')]
class TokenController extends AbstractController
{
    #[Route('/', name: 'list')]
    #[Route('/', name: 'index')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        TokenRepository $tokenRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($tokenRepository);

        return $this->render('token/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $tokenRepository->searchPaginated($pagerService),
        ]);
    }

    // TODO as global for printing
    #[Route('/print', name: 'print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tokens = $entityManager->getRepository(Token::class)->findAllOrderedByLabel();

        return $this->render('token/print.twig', ['tokens' => $tokens]);
    }


    #[NoReturn]
    #[Route('/download', name: 'download')]
    public function downloadAction(TokenRepository $tokenRepository): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_tokens_'.date('Ymd'),
            repository: $tokenRepository
        );
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $token = new Token();

        return $this->handleCreateorUpdate($request, $token, TokenForm::class);
    }

    #[Route('/{token}', name: 'view', requirements: ['token' => Requirement::DIGITS])]
    #[Route('/{token}', name: 'detail', requirements: ['token' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Token $token): RedirectResponse|Response
    {
        return $this->render('token/detail.twig', ['token' => $token]);
    }

    #[Route('/{token}/update', name: 'update', requirements: ['token' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Token $token): RedirectResponse|Response
    {
        return $this->handleCreateorUpdate(
            $request,
            $token,
            TokenForm::class
        );
    }

    // Todo translate all delete message
    #[Route('/{token}/delete', name: 'delete', requirements: ['token' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Token $token): RedirectResponse|Response
    {
        return $this->genericDelete(
            $token,
            title: $this->translator->trans('Supprimer un jeton'),
            successMsg: $this->translator->trans('Le jeton a été supprimé'),
            redirect: 'token.list',
            breadcrumb: [
                ['route' => $this->generateUrl('token.list'), 'name' => $this->translator->trans('Liste des jetons')],
                [
                    'route' => $this->generateUrl('token.detail', ['token' => $token->getId()]),
                    'name' => $token->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer un jeton')],
            ]
        );
    }

    protected function handleCreateorUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = []
    ): RedirectResponse|Response {
        return parent::handleCreateorUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans('jeton'),
                'entity_added' => $this->translator->trans('Le jeton a été ajouté'),
                'entity_updated' => $this->translator->trans('Le jeton a été mis à jour'),
                'entity_deleted' => $this->translator->trans('Le jeton a été supprimé'),
                'entity_list' => $this->translator->trans('Liste des jetons'),
                'title_add' => $this->translator->trans('Ajouter un jeton'),
                'title_update' => $this->translator->trans('Modifier un jeton'),
            ]
        );
    }
}
