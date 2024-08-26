<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\EntityRepository;
use JetBrains\PhpStorm\Deprecated;

/**
 * LarpManager\Repository\AttributeTypeRepository
 *
 * @author jsy  
 */
class AttributeTypeRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label
     * @return ArrayCollection $attributes
     */
    #[Deprecated]
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
        ->createQuery('SELECT cf FROM App\Entity\AttributeType cf ORDER BY cf.label ASC')
        ->getResult();
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.id',
            $alias.'.label',
        ];
    }

    public function sortAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
        ];
    }
}
