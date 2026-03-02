<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Connaissance;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use SensitiveParameter;

class ConnaissanceRepository extends BaseRepository
{
    /** @return list<Connaissance> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT c FROM App\Entity\Connaissance c ORDER BY c.label ASC')->getResult();
    }

    /**
     * @return ArrayCollection<int, Connaissance>
     */
    public function findAllPublicOrderedByLabel()
    {
        return $this->getEntityManager()->createQuery('SELECT c FROM App\Entity\Connaissance c WHERE c.secret = 0 ORDER BY c.label ASC')->getResult();
    }

    /**
     * @return ArrayCollection<int, Connaissance>
     */
    public function findByNiveau(int $niveau)
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c Where c.niveau = ?1 AND (c.secret = 0 OR c.secret IS NULL) ORDER BY c.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    public function getPersonnages(Connaissance $connaissance): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('p')->innerJoin('p.connaissances', 'c')->where('c.id = :cid')->setParameter('cid', $connaissance->getId());
    }

    /** @param string|array<int|string, string|array<string, mixed>|null>|null $attributes */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;

        if ('secret' === $attributes) {
            $query = $this->createQueryBuilder($alias);
            $orderBy->addOrderToQuery($query);

            return $this->secret($query, filter_var($search, \FILTER_VALIDATE_BOOLEAN));
        }

        return parent::search($search, $attributes, $orderBy, $alias);
    }

    public function secret(QueryBuilder $query, #[SensitiveParameter] bool $secret): QueryBuilder
    {
        $query->andWhere($this->alias . '.secret = :value');

        return $query->setParameter('value', $secret);
    }

    /** @return array<string, array<string, mixed>> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias . '.label', // => 'Libellé',
            $alias . '.description', // => 'Description',
            $alias . '.contraintes', // => 'Prérequis',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
            'secret' => [
                OrderBy::ASC => [$alias . '.secret' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.secret' => OrderBy::DESC],
            ],
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
            'contraintes' => $this->translator->trans('Contraintes', domain: 'repository'),
        ];
    }
}
