<?php


namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class StrategieController extends AbstractController
{
    /**
     * Présentation des constructions.
     */
    #[Route('/strategie', name: 'strategie.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoires = new ArrayCollection();

        // recherche le prochain GN
        $gnRepo = $entityManager->getRepository('\\'.\App\Entity\Gn::class);
        $gn = $gnRepo->findNext();

        $groupeRepo = $entityManager->getRepository('\\'.\App\Entity\Groupe::class);
        $groupes = $groupeRepo->findAll();

        foreach ($groupes as $groupe) {
            //  les groupes doivent participer au prochain GN
            if ($groupe->getGroupeGnById($gn->getId())) {
                foreach ($groupe->getTerritoires() as $territoire) {
                    $territoires[] = $territoire;
                }
            }
        }

        // classement des résultats
        $iterator = $territoires->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            return strcmp((string) $first->getNom(), (string) $second->getNom());
        });
        $territoires = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('admin/strategie/index.twig', [
            'gn' => $gn,
            'territoires' => $territoires,
        ]);
    }

    /**
     * Sortie CSV pour le jeu strategique.
     *
     *  Liste des fiefs /
     *  Nom du groupe qui le contrôle (vide si personne) /
     *  Niveau d'ordre social
     *  Liste des constructions sur le fief
     *  case vide (pour pouvoir le modifier)
     *  valeur de défense max
     *  valeur de défense actuelle (identique pour l'instant mais ce serait bien de prévoir que ça change)
     *  case vide (pour gérer les changements)
     *  case vide (pour mettre les horaires d'attaque ou de défense)
     */
    public function csvAction(Request $request,  EntityManagerInterface $entityManager): void
    {
        $territoires = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_economie_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output,
            [
                mb_convert_encoding('fief', 'ISO-8859-1'),
                mb_convert_encoding('groupe', 'ISO-8859-1'),
                mb_convert_encoding('Statut', 'ISO-8859-1'),
                mb_convert_encoding('Constructions', 'ISO-8859-1'),
                mb_convert_encoding('Constructions ajoutées', 'ISO-8859-1'),
                mb_convert_encoding('Résistance', 'ISO-8859-1'),
                mb_convert_encoding('Défense max', 'ISO-8859-1'),
                mb_convert_encoding('Défense actuelle', 'ISO-8859-1'),
                mb_convert_encoding('Changements', 'ISO-8859-1'),
                mb_convert_encoding('Horaires', 'ISO-8859-1'),
            ], ';');

        foreach ($territoires as $territoire) {
            $line = [];

            $line[] = mb_convert_encoding((string) $territoire->getNom(), 'ISO-8859-1');
            $groupe = $territoire->getGroupe();
            $line[] = $groupe ? mb_convert_encoding('#'.$groupe->getNumero().' '.$groupe->getNom(), 'ISO-8859-1') : mb_convert_encoding('Aucun', 'ISO-8859-1');

            $line[] = mb_convert_encoding((string) $territoire->getStatut(), 'ISO-8859-1');
            $line[] = mb_convert_encoding(implode(' - ', $territoire->getConstructions()->toArray()), 'ISO-8859-1');
            $line[] = '';
            $line[] = mb_convert_encoding((string) $territoire->getResistance(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $territoire->getDefense(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $territoire->getDefense(), 'ISO-8859-1');
            $line[] = '';
            $line[] = '';

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }
}
