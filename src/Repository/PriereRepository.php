<?php

namespace App\Repository;

use App\Entity\Personnage;
use App\Entity\Priere;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class PriereRepository extends BaseRepository
{
    /**
     * Trouve le nombre de prières correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('p'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les prières correspondant aux critères de recherche.
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
        $qb->from(Priere::class, 'p');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string)$value));

        if ($type && $value) {
            switch ($type) {
                case 'label':
                    $qb->andWhere('p.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'annonce':
                    $qb->andWhere('p.annonce LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'sphere':
                    $qb->join('p.sphere', 's');
                    $qb->andWhere('s.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('p.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('p.id = :value');
                    $qb->setParameter('value', (int)$value);
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
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias.'.sphere', 'sphere');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    // TODO Sphere as ENUM ?
    public function sphere(QueryBuilder $query, string $sphere): QueryBuilder
    {
        $query->andWhere('sphere.label = :value');

        return $query->setParameter('value', $sphere);
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.label', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.annonce',
            $alias.'.niveau',
            'sphere.label as sphere',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
            $alias.'.description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            $alias.'.annonce' => [
                OrderBy::ASC => [$alias.'.annonce' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.annonce' => OrderBy::DESC],
            ],
            $alias.'.niveau' => [
                OrderBy::ASC => [$alias.'.niveau' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.niveau' => OrderBy::DESC],
            ],
            'sphere' => [
                OrderBy::ASC => ['sphere.label' => OrderBy::ASC],
                OrderBy::DESC => ['sphere.label' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'sphere_id', 'sphere' => 'sphere',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'sphere' => $this->translator->trans('Sphere'),
            'description' => $this->translator->trans('Description'),
            'label' => $this->translator->trans('Libellé'),
            'niveau' => $this->translator->trans('Niveau'),
            'annonce' => $this->translator->trans('Annonce'),
        ];
    }

    public function getPersonnages(Priere $priere): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin(Priere::class, 'p')
            ->where('p.id = :pid')
            ->setParameter('pid', $priere->getId());
    }
}
