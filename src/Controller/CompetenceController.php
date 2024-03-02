<?php


namespace App\Controller;

use App\Entity\Competence;
use App\Repository\CompetenceRepository;
use App\Form\CompetenceForm;
use App\Form\CompetenceFindForm;
use App\Form\Entity\BaseSearch;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;


class CompetenceController extends AbstractController
{
    /**
     * Liste des compétences.
     */
    #[Route('/competence', name: 'competence.list')]
    public function indexAction(Request $request, CompetenceRepository $competenceRepository): Response
    {
        $form = $this->createForm(CompetenceFindForm::class, new BaseSearch());
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $value = $data->getValue();
        }

        $query = $competenceRepository->createQueryBuilder('c')
            ->join('c.competenceFamily', 'cf')
            ->join('c.level', 'l')
            ->addOrderBy('cf.label')
            ->addOrderBy('l.index')
        ;
        
        if (!$this->isGranted('ROLE_REGLE')) {
            $query->andWhere('c.level = 1');
        }

        if (!empty($value)) {
            $query->andWhere($query->expr()->like('cf.label', ':label'));
            $query->setParameter('label', '%'.$value.'%');
        }

        $paginator = $competenceRepository->findPaginatedQuery(
            $query->getQuery(),
            $limit = 100,
        );

        return $this->render(
            'competence/list.twig', 
            [
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Liste des perso ayant cette compétence.
     */
    public function persoAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $competence = $request->get('competence');

        return $this->render('competence/perso.twig', ['competence' => $competence]);
    }

    /**
     * Liste du matériel necessaire par compétence.
     */
    public function materielAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Competence::class);
        $competences = $repo->findAllOrderedByLabel();

        return $this->render('competence/materiel.twig', ['competences' => $competences]);
    }

    /**
     * Ajout d'une compétence.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $competence = new \App\Entity\Competence();

        // l'identifiant de la famille de competence peux avoir été passé en paramètre
        // pour initialiser le formulaire avec une valeur par défaut.
        // TODO : dans ce cas, il ne faut proposer que les niveaux pour lesquels une compétence
        // n'a pas été défini pour cette famille

        $competenceFamilyId = $request->get('competenceFamily');
        $levelIndex = $request->get('level');

        if ($competenceFamilyId) {
            $competenceFamily = $entityManager->find('\\'.\App\Entity\CompetenceFamily::class, $competenceFamilyId);
            if ($competenceFamily) {
                $competence->setCompetenceFamily($competenceFamily);
            }
        }

        if ($levelIndex) {
            $repo = $entityManager->getRepository('\\'.\App\Entity\Level::class);
            $level = $repo->findOneByIndex($levelIndex + 1);
            if ($level) {
                $competence->setLevel($level);
            }
        }

        $form = $this->createForm(CompetenceForm::class, $competence)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('competence.family', [], 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($competence);
            $entityManager->flush();

           $this->addFlash('success', 'La compétence a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('competence.detail', ['competence' => $competence->getId()]);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('competence.add', [], 303);
            }
        }

        return $this->render('competence/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'une compétence.
     */
    #[Route('/competence/{competence}', name: 'competence.detail')]
    public function detailAction(#[MapEntity] Competence $competence): Response
    {
        return $this->render('competence/detail.twig', ['competence' => $competence]);
    }

    /**
     * Met à jour une compétence.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $competence = $request->get('competence');
        $attributeRepos = $entityManager->getRepository('\\'.\App\Entity\AttributeType::class);
        $form = $this->createForm(CompetenceForm::class, $competence)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // si un document est fourni, l'enregistré
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('competence.family', [], 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            if ($form->get('update')->isClicked()) {
                $competence->setCompetenceAttributesAsString($request->get('competenceAttributesAsString'), $app['orm.em'], $attributeRepos);
                $entityManager->persist($competence);
                $entityManager->flush();
               $this->addFlash('success', 'La compétence a été mise à jour.');

                return $this->redirectToRoute('competence.detail', ['competence' => $competence->getId()]);
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($competence);
                $entityManager->flush();
               $this->addFlash('success', 'La compétence a été supprimée.');

                return $this->redirectToRoute('competence');
            }
        }

        return $this->render('competence/update.twig', [
            'competence' => $competence,
            'form' => $form->createView(),
            'available_attributes' => $attributeRepos->findAllOrderedByLabel(),
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    public function removeDocumentAction(Request $request,  EntityManagerInterface $entityManager, Competence $competence)
    {
        $competence->setDocumentUrl(null);

        $entityManager->persist($competence);
        $entityManager->flush();
       $this->addFlash('success', 'La compétence a été mise à jour.');

        return $this->redirectToRoute('competence');
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    public function getDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $competence = $request->get('competence');

        // on ne peux télécharger que les documents des compétences que l'on connait
        if (!$app['security.authorization_checker']->isGranted('ROLE_REGLE') && $this->getUser()->getPersonnage()) {
            if (!$this->getUser()->getPersonnage()->getCompetences()->contains($competence)) {
               $this->addFlash('error', "Vous n'avez pas les droits necessaires");
            }
        }

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$competence->getLabel().'.pdf"',
        ]);
    }
}
