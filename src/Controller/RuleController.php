<?php

namespace App\Controller;

use App\Entity\Rule;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Enum\Role;
use App\Form\Rule\RuleDeleteForm;
use App\Form\Rule\RuleForm;
use App\Repository\RuleRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RuleController extends AbstractController
{
    /**
     * Page de gestion des règles.
     */
    #[Route('/rule', name: 'rule.list')]
    #[Route('/rule', name: 'rules.list')]
    public function listAction(Request $request, PagerService $pagerService, RuleRepository $ruleRepository): Response
    {
        $pagerService->setRequest($request)->setRepository($ruleRepository)->setLimit(25);

        return $this->render(
            'rule\list.twig',
            [
                'pagerService' => $pagerService,
                'paginator' => $ruleRepository->searchPaginated($pagerService),
                'isAdmin' => $this->isGranted(Role::REGLE->value),
            ]
        );
    }

    /**
     * Ajout d'une règle.
     */
    #[IsGranted(Role::REGLE->value)]
    #[Route('/rule/add', name: 'rule.add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, new Rule(), RuleForm::class);
        /* OLD
        $form = $this->createForm(RuleForm::class, [])
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../private/rules/';
            $filename = $files['rule']->getClientOriginalName();
            $extension = $files['rule']->guessExtension();

            if (!$extension || 'pdf' !== $extension) {
                $this->addFlash('error', 'Désolé, votre fichier ne semble pas valide (vérifiez le format de votre fichier)');

                return $this->redirectToRoute('rules', [], 303);
            }

            $ruleFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $files['rule']->move($path, $filename);

            $rule = new Rule();
            $rule->setLabel($data['label']);
            $rule->setDescription($data['description']);
            $rule->setUrl($filename);

            $this->entityManager->persist($rule);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre fichier a été enregistrée');
        }

        return $this->render('rule/add.twig', [
            'form' => $form->createView(),
        ]);*/
    }

    /**
     * Détail d'une règle.
     */
    #[IsGranted(Role::REGLE->value)]
    #[Route('/rule/{rule}/detail', name: 'rule.detail', requirements: ['rule' => Requirement::DIGITS])]
    #[Route('/rule/{rule}', requirements: ['rule' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Rule $rule): Response
    {
        return $this->render('rule/detail.twig', [
            'rule' => $rule,
        ]);
    }

    /**
     * Mise à jour d'une règle.
     */
    #[IsGranted(Role::REGLE->value)]
    #[Route('/rule/{rule}/update', name: 'rule.update', requirements: ['rule' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Rule $rule): Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $rule,
            RuleForm::class,
        );

        /* Old
        $form = $this->createForm(RuleUpdateForm::class, $rule)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule = $form->getData();
            $this->entityManager->persist($rule);
            $this->entityManager->flush();

            $this->addFlash('success', 'Vos modifications été enregistrées');
        }

        return $this->render('rule/update.twig', [
            'form' => $form->createView(),
            'rule' => $rule,
        ]);*/
    }

    /**
     * Supression d'un fichier de règle.
     */
    #[IsGranted(Role::REGLE->value)]
    #[Route('/rule/{rule}/delete', name: 'rule.delete', requirements: ['rule' => Requirement::DIGITS])]
    public function deleteAction(Request $request, #[MapEntity] Rule $rule): RedirectResponse|Response
    {
        $form = $this->createForm(RuleDeleteForm::class, $rule)
            ->add('supprimer', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($rule);
            $this->entityManager->flush();

            $filename = __DIR__.'/../../private/rules/'.$rule->getUrl();

            if (file_exists($filename)) {
                unlink($filename);
                $this->addFlash('success', 'suppresion du fichier '.$filename);
            } else {
                $this->addFlash('error', 'impossible de supprimer le fichier '.$filename);
            }

            return $this->redirectToRoute('rules', [], 303);
        }

        return $this->render('rule/delete.twig', [
            'form' => $form->createView(),
            'rule' => $rule,
        ]);
    }

    /**
     * Télécharger une règle.
     */
    #[Route('/rule/{rule}/document', name: 'rule.document', requirements: ['rule' => Requirement::DIGITS])]
    public function documentAction(#[MapEntity] Rule $rule, Request $request): Response
    {
        return $this->sendDocument($rule, null, ! (bool) $request->get('stream', false));
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null,
    ): RedirectResponse|Response {
        if (!$entityCallback) {
            /** @var Rule $rule */
            $entityCallback = fn (mixed $rule, FormInterface $form): ?Rule => $rule->handleUpload($this->fileUploader);
        }

        if (null === $breadcrumb) {
            $breadcrumb = [['route' => $this->generateUrl('rule.list'), 'name' => 'Liste des règles']];

            if ($entity->getId()) {
                $breadcrumb[] = [
                    'route' => $this->generateUrl('rule.detail', ['rule' => $entity->getId()]),
                    'name' => $entity->getLabel(),
                ];
                $breadcrumb[] = ['name' => 'Modifier une règle'];
            }
        }

        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: [...$routes, 'root' => 'rule.'],
            msg: [
                'entity' => $this->translator->trans('Règle'),
                'entity_added' => $this->translator->trans('La règle a été ajoutée'),
                'entity_updated' => $this->translator->trans('La règle a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La règle a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des règles'),
                'title_add' => $this->translator->trans('Ajouter une règle'),
                'title_update' => $this->translator->trans('Modifier une règle'),
                ...$msg,
            ],
            entityCallback: $entityCallback
        );
    }
}
