<?php

namespace App\Repository;

use App\Entity\Personnage;
use App\Entity\Potion;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

class PotionRepository extends BaseRepository
{
    /**
     * Trouve toute les potions en fonction de leur niveau.
     *
     * @return ArrayCollection $sorts
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM App\Entity\Potion p Where p.niveau = ?1 and (p.secret = false or p.secret is null) ORDER BY p.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    /**
     * Trouve le nombre de potions correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('p'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les potions correspondant aux critères de recherche.
     */
    public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('p.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p');
        $qb->from(Potion::class, 'p');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'label':
                    $qb->andWhere('p.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('p.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'numero':
                    $qb->andWhere('p.numero = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
                case 'id':
                    $qb->andWhere('p.id = :value');
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
            $alias.'.niveau',
            $alias.'.numero',
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
            'numero' => [OrderBy::ASC => [$alias.'.numero' => OrderBy::ASC], OrderBy::DESC => [$alias.'.numero' => OrderBy::DESC]],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description'),
            'label' => $this->translator->trans('Libellé'),
            'niveau' => $this->translator->trans('Niveau'),
            'numero' => $this->translator->trans('Numero'),
        ];
    }

    public function getPersonnages(Potion $potion): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin(Potion::class, 'p')
            ->where('p.id = :pid')
            ->setParameter('pid', $potion->getId());
    }
}
