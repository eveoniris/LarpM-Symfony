<?php


namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Trombinoscope\TrombinoscopeForm;
use App\Repository\ParticipantRepository;
use App\Service\PagerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class TrombinoscopeController extends AbstractController
{
    /**
     * Le trombinoscope général.
     */
    #[Route('/trombinoscope', name: 'trombinoscope.index')]
    public function indexAction(Request $request, PagerService $pagerService, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager): Response
    {
        $gnRepo = $entityManager->getRepository('\\'.\App\Entity\Gn::class);
        $gn = $gnRepo->findNext();

        $pagerService->setRequest($request)->setRepository($participantRepository)->setLimit(50);
        //$paginator = $participantRepository->searchPaginatedByGn($pagerService, $gn->getId());
        //dump($paginator);

        return $this->render('trombinoscope.twig', [
            'gn' => $gn,
            'pagerService' => $pagerService,
            'paginator' => $participantRepository->searchPaginatedByGn($pagerService, $gn->getId()),
        ]);

        /*


        $form = $this->createForm(TrombinoscopeForm::class);

        $form->handleRequest($request);

        $renomme = 0;
        $territoire = null;
        $competence = null;
        $classe = null;
        $religion = null;
        $language = null;
        $groupe = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['renomme']) {
                $renomme = $data['renomme'];
            }

            if ($data['territoire']) {
                $territoire = $data['territoire'];
            }

            if ($data['classe']) {
                $classe = $data['classe'];
            }

            if ($data['competence']) {
                $competence = $data['competence'];
            }

            if ($data['religion']) {
                $religion = $data['religion'];
            }

            if ($data['language']) {
                $language = $data['language'];
            }

            if ($data['groupe']) {
                $groupe = $data['groupe'];
            }
        }

        $participants = new ArrayCollection();
        foreach ($gn->getParticipants() as $participant) {
            if ($participant->getPersonnage()
                && $participant->getPersonnage()->getRenomme() >= $renomme
                && (!$territoire || ($participant->getGroupeGn() && $territoire == $participant->getGroupeGn()->getGroupe()->getTerritoire()))
                && (!$classe || ($participant->getPersonnage()->getClasse() == $classe))
                && (!$competence || $participant->getPersonnage()->isKnownCompetence($competence))
                && (!$religion || $participant->getPersonnage()->isKnownReligion($religion))
                && (!$language || $participant->getPersonnage()->isKnownLanguage($language))
                && (!$groupe || ($participant->getGroupeGn() && $groupe == $participant->getGroupeGn()->getGroupe()))) {
                $participants[] = $participant;
            }
        }

        return $this->render('trombinoscope.twig', [
            'gn' => $gn,
            'participants' => $participants,
            'form' => $form->createView(),
            'renomme' => $renomme,
            'territoire' => $territoire,
            'classe' => $classe,
            'competence' => $competence,
            'religion' => $religion,
            'language' => $language,
            'groupe' => $groupe,
        ]);*/
    }

    /**
     * Permet de selectionner des personnages pour faire un trombinoscope.
     */
    #[Route('/trombinoscope/perso', name: 'trombinoscope.perso')]
    public function persoAction(Request $request,  EntityManagerInterface $entityManager): Response
    {
        $personnages = null;
        $titre = null;

        $form = $this->createForm()
            ->add('titre', 'text', [
                'label' => 'Le titre de votre sélection',
            ])
            ->add('ids', 'textarea', [
                'label' => 'Indiquez les numéros des personnages séparé d\'un espace',
            ])
            ->add('send', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Envoyer'])
            ->add('print', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Imprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $titre = $data['titre'];
            $ids = $data['ids'];
            $ids = explode(' ', (string) $ids);
            $repo = $entityManager->getRepository('\\'.\App\Entity\Personnage::class);
            $personnages = $repo->findByIds($ids);

            if ($form->get('print')->isClicked()) {
                return $this->render('trombinoscopePersoPrint.twig', [
                    'titre' => $titre,
                    'personnages' => $personnages,
                ]);
            }
        }

        return $this->render('trombinoscopePerso.twig', [
            'titre' => $titre,
            'personnages' => $personnages,
            'form' => $form->createView(),
        ]);
    }
}
