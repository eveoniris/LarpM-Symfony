<?php

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnagesReligions;
use App\Entity\User;
use App\Service\OrderBy;
use App\Service\PagerService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ParticipantRepository extends BaseRepository
{
    /**
     * Trouve le nombre d'utilisateurs correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('p'));
        $qb->from(Participant::class, 'p');
        $qb->join('p.gn', 'gn');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function searchPaginatedByGn(PagerService $pageRequest, int $gnid): Paginator
    {
        $query = $this->searchByGn(
            $gnid,
            $pageRequest->getSearchValue(),
            $pageRequest->getSearchType(),
            $pageRequest->getOrderBy(),
            $this->getAlias(),
        )->getQuery();

        return $this->findPaginatedQuery(
            $query,
            $pageRequest->getLimit(),
            $pageRequest->getPage()
        );
    }

    public function searchByGn(
        int $gnid,
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);

        $query->andWhere($alias.'.personnage is not null');
        $query->andWhere($alias.'.gn = :value');
        $query->setParameter('value', $gnid);

        $query->join($alias.'.groupeGn', 'groupeGn');
        $query->join('groupeGn.groupe', 'groupe');
        $query->join($alias.'.personnage', 'personnage');
        $query->join('personnage.classe', 'classe');

        // $query->leftJoin($alias.'.player', 'player');
        // $query->join($alias.'.user', 'user');

        switch ($attributes) {
            case 'religion.label':
                $religion = $search;
                $sub = new QueryBuilder($this->getEntityManager());
                $sub->select('pr');
                $sub->from(PersonnagesReligions::class, 'pr');
                $sub->join('pr.religion', 'religion');
                $sub->andWhere('pr.personnage = '.$alias.'.personnage');
                $sub->andWhere('religion.label like :religion');

                $query->andWhere($query->expr()->exists($sub->getDQL()));
                $query->setParameter('religion', '%'.$religion.'%');
                $search = '';
                break;

            case 'competence.description':
                $competence = $search;
                $sub = new QueryBuilder($this->getEntityManager());
                $sub->select('pe');
                $sub->from(Personnage::class, 'pe');
                $sub->join('pe.competences', 'competence');
                $sub->join('competence.competenceFamily', 'competenceFamily');
                $sub->andWhere('pe.id = '.$alias.'.personnage');
                $sub->andWhere('competenceFamily.label like :competence');

                $query->andWhere($query->expr()->exists($sub->getDQL()));
                $query->setParameter('competence', '%'.$competence.'%');
                $search = '';
                break;

            case 'langue.label':
                $langue = $search;
                $sub = new QueryBuilder($this->getEntityManager());
                $sub->select('pl');
                $sub->from(PersonnageLangues::class, 'pl');
                $sub->join('pl.langue', 'langue');
                $sub->andWhere('pl.personnage = '.$alias.'.personnage');
                $sub->andWhere('langue.label like :langue');

                $query->andWhere($query->expr()->exists($sub->getDQL()));
                $query->setParameter('langue', '%'.$langue.'%');
                $search = '';
                break;

            case 'territoire.nom':
                $territoire = $search;
                $query->join('personnage.territoire', 'territoire');
                $query->andWhere('territoire.nom like :territoire');
                $query->setParameter('territoire', '%'.$territoire.'%');
                $search = '';
                break;

            case 'participant.renommee':
                $renommee = $search;
                $query->andWhere('personnage.renomme >= :renommee');
                $query->setParameter('renommee', (int) $renommee);
                $search = '';
                break;
        }

        return parent::search($search, $attributes, $orderBy, $alias, $query);
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
        $query->join($alias.'.user', 'user');
        $query->join($alias.'.gn', 'gn');
        $query->join('user.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    /*public function sortAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        dump(1);

        return [
            //'groupe' => [OrderBy::ASC => ['groupe' => OrderBy::ASC], OrderBy::DESC => ['groupe' => OrderBy::DESC]],
            ...parent::sortAttributes($alias),
            // using alias like "scriptwriter" require another template loop due to the added $query->select() entity
            //'user.username' => [OrderBy::ASC => ['user.username' => OrderBy::ASC], OrderBy::DESC => ['user.username' => OrderBy::DESC]],
            //'player.username' => [OrderBy::ASC => ['player.username' => OrderBy::ASC], OrderBy::DESC => ['player.username' => OrderBy::DESC]],
        ];
    }*/

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.renommee',
            'territoire.nom as territoire',
            'competence.description as competence',
            'classe.label_masculin as classe',
            'religion.label as religion',
            'langue.label as langue',
            'groupe.nom as groupe',
        ];
    }

    /*public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'scriptwriter' => 'user',
            'groupe_id' => 'groupe',
            'player_id', 'auteur' => 'player',
            'classe', 'classe_id' => 'classe',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }*/

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'renommee' => $this->translator->trans('Renommée', domain: 'repository'),
            'territoire' => $this->translator->trans('Territoire', domain: 'repository'),
            'competence' => $this->translator->trans('Compétence', domain: 'repository'),
            'classe' => $this->translator->trans('Classe', domain: 'repository'),
            'religion' => $this->translator->trans('Religion', domain: 'repository'),
            'langue' => $this->translator->trans('Langue', domain: 'repository'),
            'groupe' => $this->translator->trans('Groupe', domain: 'repository'),
        ];
    }

    public function gn(QueryBuilder $query, Gn $gn): QueryBuilder
    {
        $query->andWhere($this->alias.'.gn = :value');

        return $query->setParameter('value', $gn->getId());
    }

    public function getPersonnages(Participant $participant): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        $queryBuilder = $personnageRepository->createQueryBuilder('perso');

        return $personnageRepository->user($queryBuilder, $participant->getUser());
    }

    public function getPersonnagesAlive(Participant $participant): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        $queryBuilder = $this->getPersonnages($participant);

        return $personnageRepository->alive($queryBuilder, true);
    }
}
