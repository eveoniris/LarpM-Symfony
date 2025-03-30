<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class ItemRepository extends BaseRepository
{
    public function findNextNumero(): int
    {
        try {
            $numeroMax = (int) $this->getEntityManager()
                ->createQuery('SELECT MAX(i.numero) FROM App\Entity\Item i')
                ->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException $e) {
            // LOG ?
            $numeroMax = 0;
        }

        return $numeroMax++;
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias.'.quality', 'quality');

        // Ad Paginator didn't like a dynamic concat we add it here and in SearchAttributes with doctrine HIDDEN keywork
        $query->addSelect('CONCAT(quality.numero, item.identification) AS HIDDEN qualident');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.numero',
            $alias.'.identification',
            'quality.numero as quality',
            // HIDDEN keyword force Doctrine to do NOT map/hydrate this select in the result,
            // because Paginator do not allow Scalar hydratation
            // Will got a result with [[Item(), 0 => qualident]] insead of [[Item()]] with a dynamic qualident fields
            'CONCAT(quality.numero, item.identification) AS HIDDEN qualident',
            $alias.'.label',
            $alias.'.description',
            $alias.'.special',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'numero' => [
                OrderBy::ASC => [$alias.'.numero' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.numero' => OrderBy::DESC],
            ],
            'identification' => [
                OrderBy::ASC => [$alias.'.identification' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.identification' => OrderBy::DESC],
            ],
            'quality' => [
                OrderBy::ASC => ['quality.numero' => OrderBy::ASC],
                OrderBy::DESC => ['quality.numero' => OrderBy::DESC],
            ],
            'label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'special' => [
                OrderBy::ASC => [$alias.'.special' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.special' => OrderBy::DESC],
            ],
            'qualident' => [
                OrderBy::ASC => ['qualident' => OrderBy::ASC],
                OrderBy::DESC => ['qualident' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'quality_id', 'quality' => 'quality',
            'HIDDEN qualident', 'qualident' => 'qualident',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'special' => $this->translator->trans('Spécial', domain: 'repository'),
            'numero' => $this->translator->trans('Numéro', domain: 'repository'),
            'quality' => $this->translator->trans('Qualité', domain: 'repository'),
            'qualident' => $this->translator->trans('Qualité et identification', domain: 'repository'),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
            'identification' => $this->translator->trans('Identification', domain: 'repository'),
        ];
    }
}
