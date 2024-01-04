<?php


namespace App\Controller;

use App\Entity\Rule;
use App\Repository\RuleRepository;
use App\Form\Rule\RuleDeleteForm;
use App\Form\Rule\RuleForm;
use App\Form\Rule\RuleUpdateForm;

//use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\RuleController.
 *
 * @author kevin
 */
class RuleController extends AbstractController
{
    /**
     * Page de gestion des règles.
     */
    #[Route('/rule', name: 'rules')]
    public function listAction(Request $request, RuleRepository $ruleRepository): Response
    {
        $orderBy = $this->getRequestOrder(
            alias: 'r',
            allowedFields: $ruleRepository->getFieldNames()
        );

        $query = $ruleRepository->createQueryBuilder('r')
            ->orderBy(key($orderBy), current($orderBy));

        $regles = $ruleRepository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(), $this->getRequestPage()
        );

        return $this->render(
            'rule\list.twig',
            [
                'paginator' => $regles,
            ]
        );


        //$regles = $entityManager->getRepository(\App\Entity\Rule::class)->findAll();

        //return $this->render('rule/list.twig', [
        //    'regles' => $regles,
        //]);
    }

    /**
     * Ajout d'une règle.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(RuleForm::class(), [])
            ->add('envoyer', 'submit', ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/rules/';
            $filename = $files['rule']->getClientOriginalName();
            $extension = $files['rule']->guessExtension();

            if (!$extension || 'pdf' != $extension) {
               $this->addFlash('error', 'Désolé, votre fichier ne semble pas valide (vérifiez le format de votre fichier)');

                return $this->redirectToRoute('rules', [], 303);
            }

            $ruleFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $files['rule']->move($path, $filename);

            $rule = new \App\Entity\Rule();
            $rule->setLabel($data['label']);
            $rule->setDescription($data['description']);
            $rule->setUrl($filename);

            $entityManager->persist($rule);
            $entityManager->flush();

           $this->addFlash('success', 'Votre fichier a été enregistrée');
        }

        return $this->render('rule/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une règle.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Rule $rule)
    {
        return $this->render('rule/detail.twig', [
            'rule' => $rule,
        ]);
    }

    /**
     * Mise à jour d'une règle.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Rule $rule)
    {
        $form = $this->createForm(RuleUpdateForm::class(), $rule)
            ->add('envoyer', 'submit', ['label' => 'Envoyer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule = $form->getData();
            $entityManager->persist($rule);
            $entityManager->flush();

           $this->addFlash('success', 'Vos modifications été enregistrées');
        }

        return $this->render('rule/update.twig', [
            'form' => $form->createView(),
            'rule' => $rule,
        ]);
    }

    /**
     * Supression d'un fichier de règle.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Rule $rule)
    {
        $form = $this->createForm(RuleDeleteForm::class(), $rule)
            ->add('supprimer', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($rule);
            $entityManager->flush();

            $filename = __DIR__.'/../../../private/rules/'.$rule->getUrl();

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
    public function documentAction(Request $request,  EntityManagerInterface $entityManager, Rule $rule)
    {
        $filename = __DIR__.'/../../../private/rules/'.$rule->getUrl();

        return $app->sendFile($filename);
    }
}
