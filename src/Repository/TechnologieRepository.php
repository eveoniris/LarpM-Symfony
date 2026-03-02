<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Personnage;
use App\Entity\Technologie;
use App\Enum\CompetenceFamilyType;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class TechnologieRepository extends BaseRepository
{
    /**
     * Find all public technologies ordered by label.
     */
    /** @return list<Technologie> */
    public function findPublicOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Technologie r WHERE r.secret = 0 ORDER BY r.label ASC')->getResult();
    }

    /**
     * Find all technologies ordered by label.
     */
    /** @return list<Technologie> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Technologie r ORDER BY r.label ASC')->getResult();
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
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.competenceFamily', 'competenceFamily');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function competenceFamily(QueryBuilder $query, CompetenceFamilyType $competenceFamilyType): QueryBuilder
    {
        $query->andWhere('competenceFamily.label = :value');

        return $query->setParameter('value', $competenceFamilyType->value);
    }

    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.label', // => 'Libellé',
            $alias . '.description', // => 'Description',
            'competenceFamily.label as competenceFamily',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias . '.label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
            $alias . '.description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
            'competenceFamily' => [
                OrderBy::ASC => ['competenceFamily.label' => OrderBy::ASC],
                OrderBy::DESC => ['competenceFamily.label' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'competence_family_id', 'competence_family' => 'competenceFamily',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'competenceFamily' => $this->translator->trans('Expert', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'label' => $this->translator->trans('Nom', domain: 'repository'),
        ];
    }

    public function getPersonnages(Technologie $technologie): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')->innerJoin('perso.technologies', 't')->where('t.id = :tid')->setParameter('tid', $technologie->getId());
    }
}
