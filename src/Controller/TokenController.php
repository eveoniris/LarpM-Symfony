<?php

namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenForm;
use App\Repository\TokenRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    // TODO
    #[Route('/print', name: 'print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tokens = $entityManager->getRepository('\\'.Token::class)->findAllOrderedByLabel();

        return $this->render('token/print.twig', ['tokens' => $tokens]);
    }

    /**
     * Téléchargement des tokens.
     */
    // TODO
    #[Route('/download', name: 'download')]
    public function downloadAction(Request $request, EntityManagerInterface $entityManager): void
    {
        $tokens = $entityManager->getRepository('\\'.Token::class)->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_tokens_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'id',
                'label',
                'tag',
                'description'], ';');

        foreach ($tokens as $token) {
            fputcsv($output, $token->getExportValue(), ';');
        }

        fclose($output);
        exit;
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $token = new Token();

        return $this->handleCreateorUpdate($request, $token, TokenForm::class);
    }
    /*public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $token = new Token();

        $form = $this->createForm(TokenForm::class, $token)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $form->getData();

            $entityManager->persist($token);
            $entityManager->flush($token);

            $this->addFlash('success', 'Le jeton a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('token.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('token.add', [], 303);
            }
        }

        return $this->render('token/add.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }*/

    #[Route('/{token}', name: 'view', requirements: ['token' => Requirement::DIGITS])]
    #[Route('/{token}', name: 'detail', requirements: ['token' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Token $token): RedirectResponse|Response
    {
        return $this->render('token/detail.twig', ['token' => $token]);
    }
    /*public function detailAction(Request $request, EntityManagerInterface $entityManager, Token $token): Response
    {
        return $this->render('token/detail.twig', ['token' => $token]);
    }*/

    #[Route('/{token}/update', name: 'update', requirements: ['token' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Token $token): RedirectResponse|Response
    {
        return $this->handleCreateorUpdate(
            $request,
            $token,
            TokenForm::class
        );
    }
    /*public function updateAction(Request $request, EntityManagerInterface $entityManager, Token $token): RedirectResponse|Response
    {
        $form = $this->createForm(TokenForm::class, $token)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $form->getData();

            $entityManager->persist($token);
            $entityManager->flush($token);

            $this->addFlash('success', 'Le jeton a été modifié.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $this->render('token/update.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }*/

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
    /*public function deleteAction(Request $request, EntityManagerInterface $entityManager, Token $token): RedirectResponse|Response
    {
        $form = $this->createForm(TokenDeleteForm::class, $token)
            ->add('save', SubmitType::class, ['label' => 'Supprimer', 'attr' => ['class' => 'btn-danger']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($token);
            $entityManager->flush($token);

            $this->addFlash('success', 'Le jeton a été supprimé.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $this->render('token/delete.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }*/

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
