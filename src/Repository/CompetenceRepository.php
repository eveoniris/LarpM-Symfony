<?php

namespace App\Repository;

use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\LevelType;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

class CompetenceRepository extends BaseRepository
{
    public function competenceFamily(QueryBuilder $query, CompetenceFamily $competenceFamily): QueryBuilder
    {
        $query->andWhere($this->alias.'.competenceFamily = :value');

        return $query->setParameter('value', $competenceFamily);
    }

    /**
     * Find all competences ordered by label.
     *
     * @return ArrayCollection $competences
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT c FROM App\Entity\Competence c JOIN c.competenceFamily cf JOIN c.level l ORDER BY cf.label ASC, l.index ASC',
            )
            ->getResult();
    }

    public function getPersonnages(Competence $competence): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('p')
            ->innerJoin('p.competences', 'c')
            ->where('c.id = :cid')
            ->setParameter('cid', $competence->getId());
    }

    /**
     * Returns a query builder to find all competences ordered by label.
     */
    public function getQueryBuilderFindAllOrderedByLabel(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(Competence::class, 'c')
            ->join('c.competenceFamily', 'cf')
            ->join('c.level', 'l')
            ->addOrderBy('cf.label')
            ->addOrderBy('l.index');
    }

    /**
     * Fourni la liste des compétences de premier niveau.
     */
    public function getRootCompetences(): ArrayCollection
    {
        $rootCompetences = new ArrayCollection();

        $repo = $this->entityManager->getRepository(CompetenceFamily::class);
        $competenceFamilies = $repo->findAll();

        foreach ($competenceFamilies as $competenceFamily) {
            $competence = $competenceFamily->getFirstCompetence();
            if ($competence) {
                $rootCompetences->add($competence);
            }
        }

        // trie des competences disponibles
        $iterator = $rootCompetences->getIterator();
        $iterator->uasort(static fn($a, $b) => $a->getLabel() <=> $b->getLabel());

        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function level(QueryBuilder $query, LevelType|Level $level): QueryBuilder
    {
        $query->andWhere($this->alias.'.level = :level');

        $value = $level;
        if ($level instanceof LevelType) {
            $value = $level->getId();
        }

        return $query->setParameter('level', $value);
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
        $query->join($alias.'.competenceFamily', 'competenceFamily');
        $query->join($alias.'.level', 'level');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            'level.label as level',
            $alias.'.description', // => 'Description',
            $alias.'.materiel',
            'competenceFamily.label as competenceFamily',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'competenceFamily' => [
                OrderBy::ASC => ['competenceFamily.label' => OrderBy::ASC],
                OrderBy::DESC => ['competenceFamily.label' => OrderBy::DESC],
            ],
            'level' => [
                OrderBy::ASC => ['level.label' => OrderBy::ASC],
                OrderBy::DESC => ['level.label' => OrderBy::DESC],
            ],
            ...parent::sortAttributes($alias),
            'description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'materiel' => [
                OrderBy::ASC => [$alias.'.materiel' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.materiel' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'competence_family_id', 'competence_family', 'competenceFamily' => 'competenceFamily',
            'level_id', 'level' => 'level',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'level' => $this->translator->trans('Niveau', domain: 'repository'),
            'materiel' => $this->translator->trans('Matériel', domain: 'repository'),
            'competenceFamily' => $this->translator->trans('Libellé', domain: 'repository'),
        ];
    }

    public function userHasCompetence(User $user, Competence $competence): bool
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('exist', 'exist', 'boolean');

        /** @noinspection SqlNoDataSourceInspection */
        $query = $this->getEntityManager()
            ->createNativeQuery(
                <<<SQL
                SELECT 1 as exist 
                FROM `user` u
                INNER JOIN participant p ON u.id = p.user_id
                INNER JOIN personnage pe ON p.personnage_id = pe.id
                INNER JOIN personnages_competences pc ON pe.id = pc.personnage_id
                WHERE pc.competence_id = :cid and u.id = :uid
                LIMIT 1;
                SQL,
                $rsm,
            )
            ->setParameter('uid', $user->getId())
            ->setParameter('cid', $competence->getId());

        return (bool)$query->getResult();
    }
}
