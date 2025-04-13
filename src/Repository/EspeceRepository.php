<?php

namespace App\Repository;

use App\Entity\Espece;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class EspeceRepository extends BaseRepository
{

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.nom', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.type',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.nom' => [
                OrderBy::ASC => [$alias.'.nom' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.nom' => OrderBy::DESC],
            ],
            $alias.'.description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'type' => [
                OrderBy::ASC => [$alias.'.type' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.type' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'nom' => $this->translator->trans('Nom', domain: 'repository'),
            'type' => $this->translator->trans('Type', domain: 'repository'),
        ];
    }

    public function getPersonnages(Espece $espece): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin('perso.especes', 'esp')
            ->where('esp.id = :espid')
            ->setParameter('espid', $espece->getId());
    }
}
