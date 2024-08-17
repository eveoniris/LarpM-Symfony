<?php


namespace App\Repository;

use App\Entity\Personnage;
use App\Entity\Sort;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SortRepository extends BaseRepository
{
    /**
     * Find all Apprenti sorts ordered by label.
     *
     * @return ArrayCollection $sorts
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT s FROM App\Entity\Sort s Where s.niveau = ?1 AND (s.secret = 0 OR s.secret IS NULL) ORDER BY s.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    /**
     * Trouve le nombre de sorts correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('s'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les sorts correspondant aux critères de recherche.
     */
    public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('s.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s');
        $qb->from(\App\Entity\Sort::class, 's');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'label':
                    $qb->andWhere('s.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'domaine':
                    $qb->join('s.domaine', 'd');
                    $qb->andWhere('d.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('s.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('s.id = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
            }
        }

        return $qb;
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

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.label', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.niveau', // => 'Description',
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
            'niveau' => [OrderBy::ASC => [$alias.'.niveau' => OrderBy::ASC], OrderBy::DESC => [$alias.'.niveau' => OrderBy::DESC]],
        ];
    }

    public function getPersonnages(Sort $sort): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);
        return $personnageRepository->createQueryBuilder('p')
            ->innerJoin(Sort::class, 's')
            ->where('s.id = :sid')
            ->setParameter('sid', $sort->getId());
    }
}
