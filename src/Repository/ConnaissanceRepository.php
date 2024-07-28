<?php

namespace App\Repository;

use App\Entity\Connaissance;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

class ConnaissanceRepository extends BaseRepository
{
    /**
     * @return ArrayCollection $Connaissance
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c Where c.niveau = ?1 AND (c.secret = 0 OR c.secret IS NULL) ORDER BY c.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c ORDER BY c.label ASC')
            ->getResult();
    }

    /**
     * @return ArrayCollection $religions
     */
    public function findAllPublicOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c WHERE c.secret = 0 ORDER BY c.label ASC')
            ->getResult();
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;

        if ('secret' === $attributes) {
            $query = $this->createQueryBuilder($alias)
                ->orderBy($orderBy->getSort(), $orderBy->getOrderBy());

            return $this->secret(
                $query,
                filter_var($search, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return parent::search($search, $attributes, $orderBy, $alias);
    }

    public function secret(QueryBuilder $query, bool $secret): QueryBuilder
    {
        $query->andWhere($this->alias.'.secret = :value');

        return $query->setParameter('value', $secret);
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.label', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.contraintes', // => 'Prérequis',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
            'description' => [OrderBy::ASC => [$alias.'.description' => OrderBy::ASC], OrderBy::DESC => [$alias.'.description' => OrderBy::DESC]],
            'secret' => [OrderBy::ASC => [$alias.'.secret' => OrderBy::ASC], OrderBy::DESC => [$alias.'.secret' => OrderBy::DESC]],
        ];
    }

    public function getPersonnages(Connaissance $connaissance): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);
        return $personnageRepository->createQueryBuilder('p')
            ->innerJoin(Connaissance::class, 'c')
            ->where('c.id = :cid')
            ->setParameter('cid', $connaissance->getId());
    }
}
