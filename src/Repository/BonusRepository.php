<?php

namespace App\Repository;

use App\Entity\Bonus;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class BonusRepository extends BaseRepository
{
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.titre',
            $alias.'.description',
            $alias.'.type',
            $alias.'.period',
            $alias.'.application',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.titre' => [
                OrderBy::ASC => [$alias.'.titre' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.titre' => OrderBy::DESC],
            ],
            $alias.'.description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            $alias.'.type' => [
                OrderBy::ASC => [$alias.'.type' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.type' => OrderBy::DESC],
            ],
            $alias.'.periode' => [
                OrderBy::ASC => [$alias.'.periode' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.periode' => OrderBy::DESC],
            ],
            $alias.'.application' => [
                OrderBy::ASC => [$alias.'.application' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.application' => OrderBy::DESC],
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

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'titre' => $this->translator->trans('Titre', domain: 'repository'),
            'type' => $this->translator->trans('Type', domain: 'repository'),
            'periode' => $this->translator->trans('Périodicité', domain: 'repository'),
            'application' => $this->translator->trans("Domaine d'application", domain: 'repository'),
        ];
    }

    /**
     * Uniquement les personnage_bonus
     * Pas les origines ou groupes.
     */
    public function getPersonnages(Bonus $bonus): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin('perso.personnageBonus', 'pb')
            ->innerJoin('pb.bonus', 'bonus')
            ->where('bonus.id = :bonusid')
            ->setParameter('bonusid', $bonus->getId());
    }
}
