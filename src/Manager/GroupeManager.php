<?php

namespace App\Manager;

use App\Entity\Groupe;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

final class GroupeManager
{
    /**
     * Génére une quête commerciale pour un groupe donné.
     */
    public static function generateQuete(Groupe $groupe, $ressourceCommunes, $ressourceRares)
    {
        $tab_recompenses = [
            "1 pièce d'or (10 pièces d'argent)",
            '1 point de renommée',
            "1 point d'héroïsme",
            '2 ingrédients au choix',
            '4 ressources communes choisies',
            '2 ressources rares choisies',
            '6 ressources communes au hasard',
            '3 ressouces rare au hasard',
            '2 points de rituel',
            '2 Perles',
            '1 formule alchimique apprenti ou initié au hasard',
            '1 sortilège apprenti ou initié au hasard',
            '2 potions alchimiques apprenti ou initié au hasard',
            '1 potion alchimique apprenti ou initié au choix',
            '1 réponse à une question simple',
            '1 carte aventure (jeu maritime)',
            '5 ingrédients apprenti au hasard',
            '2 ingrédients expert au hasard',
            '1 ingrédient maître au hasard',
        ];

        $cible = null;
        if ($groupe->getTerritoire()) {
            $tabCible = $groupe->getTerritoire()->getTerritoireCibles()->toArray();
            shuffle($tabCible);
            if (count($tabCible) > 0) {
                $cible = $tabCible[0];
            }
        }

        $needs = new ArrayCollection();
        $recompenses = new ArrayCollection();
        $px = 0;
        $importation_needed = 0;
        $common_ressources_needed = 0;
        $uncommon_ressources_needed = 0;
        $valeur = 0;

        // importations du territoire
        $importations = $groupe->getImportations();
        $exportations = $groupe->getExportations();

        // ressources rares
        // en retirer les exportations du pays
        $ressourceRares = new ArrayCollection(array_diff($ressourceRares->toArray(), $exportations->toArray()));

        // ressources communes
        // en retirer les exportations du pays
        $ressourceCommunes = new ArrayCollection(array_diff($ressourceCommunes->toArray(), $exportations->toArray()));

        // calcul du nombre d'importation necessaire
        /*if ( $importations->count() > 3 ) $importations_needed = 3;
        else if ( $importations->count() > 0 ) $importation_needed = rand(1,$importations->count());*/

        // calcul du nombre de ressources communes
        $common_ressources_needed = 3;
        if ($common_ressources_needed < 0) {
            $common_ressources_needed = 0;
        }

        // calcul du nombre de ressources rares
        $uncommon_ressources_needed = 4;
        if ($uncommon_ressources_needed < 0) {
            $uncommon_ressources_needed = 0;
        }

        // allocation des importations
        if ($importation_needed > 0) {
            $resArray = $importations->toArray();
            shuffle($resArray);
            $needs = new ArrayCollection(array_merge($needs->toArray(), array_slice($resArray, 0, $importation_needed)));
        }

        // allocation des ressources simples
        $ressourceCommunes = new ArrayCollection(array_diff($ressourceCommunes->toArray(), $needs->toArray()));
        if ($ressourceCommunes->count() > 0) {
            $resArray = $ressourceCommunes->toArray();
            shuffle($resArray);
            $needs = new ArrayCollection(array_merge($needs->toArray(), array_slice($resArray, 0, $common_ressources_needed)));
        }

        // allocation des ressources rares
        $ressourceRares = new ArrayCollection(array_diff($ressourceRares->toArray(), $needs->toArray()));
        if ($ressourceRares->count() > 0) {
            $resArray = $ressourceRares->toArray();
            shuffle($resArray);
            $needs = new ArrayCollection(array_merge($needs->toArray(), array_slice($resArray, 0, $uncommon_ressources_needed)));
        }

        // calcul de la valeur de ce qui est demandé
        $valeur = 0;
        foreach ($needs as $ressource) {
            $rarete = $ressource->getRarete();
            switch ($rarete->getValue()) {
                case 1: $valeur += 3;
                    break;
                case 2: $valeur += 6;
                    break;
            }
        }

        // choix de la récompense
        shuffle($tab_recompenses);
        $recompenses = array_slice($tab_recompenses, 0, 4);
        $recompenses[] = "1 point d'expérience";

        return [
            'needs' => $needs,
            'cible' => $cible,
            'valeur' => $valeur,
            'recompenses' => $recompenses,
        ];
    }

    /**
     * Fourni le gn actif.
     */
    public static function getGnActif(EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository(Gn::class);

        return $repo->findNext();
    }
}
