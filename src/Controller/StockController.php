<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Localisation;
use App\Entity\Objet;
use App\Entity\Proprietaire;
use App\Entity\Rangement;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockController extends AbstractController
{
    #[Route('/stock', name: 'stock.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.Objet::class);

        $qb = $repo->createQueryBuilder('objet');
        $qb->select('COUNT(objet)');

        $objet_count = $qb->getQuery()->getSingleScalarResult();

        $qb = $repo->createQueryBuilder('o');
        $qb->select('COUNT(o)');
        $qb->where('o.proprietaire IS NULL');

        $objet_without_proprio_count = $qb->getQuery()->getSingleScalarResult();

        $qb = $repo->createQueryBuilder('o');
        $qb->select('COUNT(o)');
        $qb->where('o.user IS NULL');

        $objet_without_responsable_count = $qb->getQuery()->getSingleScalarResult();

        $qb = $repo->createQueryBuilder('o');
        $qb->select('COUNT(o)');
        $qb->where('o.rangement IS NULL');

        $objet_without_rangement_count = $qb->getQuery()->getSingleScalarResult();

        $last_add = $repo->findBy([], ['creation_date' => 'DESC'], 10, 0);

        $repo = $entityManager->getRepository('\\'.Etat::class);
        $etats = $repo->findAll();

        $repo = $entityManager->getRepository('\\'.Tag::class);
        $tags = $repo->findAll();

        $repo = $entityManager->getRepository('\\'.Localisation::class);
        $localisations = $repo->findAll();

        $repo = $entityManager->getRepository('\\'.Rangement::class);
        $rangements = $repo->findAll();

        $repo = $entityManager->getRepository('\\'.Proprietaire::class);
        $proprietaires = $repo->findAll();

        return $this->render('stock/index.twig', [
            'objet_count' => $objet_count,
            'last_add' => $last_add,
            'objet_without_proprio_count' => $objet_without_proprio_count,
            'objet_without_responsable_count' => $objet_without_responsable_count,
            'objet_without_rangement_count' => $objet_without_rangement_count,
            'etats' => $etats,
            'tags' => $tags,
            'localisations' => $localisations,
            'rangements' => $rangements,
            'proprietaires' => $proprietaires,
        ]);
    }
}
