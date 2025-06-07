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
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    public function findAllWithoutPersonnageSecondaire(Gn $gn)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                    SELECT p FROM App\Entity\Participant p
                    WHERE p.personnageSecondaire IS NULL and p.gn = :gnid
                DQL,
            );
        $query->setParameter('gnid', $gn->getId());

        return $query->getResult();
    }

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

    public function getPersonnagesAlive(Participant $participant): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        $queryBuilder = $this->getPersonnages($participant);

        return $personnageRepository->alive($queryBuilder, true);
    }

    public function getPersonnages(Participant $participant): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        $queryBuilder = $personnageRepository->createQueryBuilder('perso');

        return $personnageRepository->user($queryBuilder, $participant->getUser());
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

    public function gn(QueryBuilder $query, Gn $gn): QueryBuilder
    {
        $query->andWhere($this->alias.'.gn = :gnId');

        return $query->setParameter('gnId', $gn->getId());
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
            $pageRequest->getPage(),
        );
    }

    public function searchByGn(
        int $gnid,
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
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
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias.'.user', 'user');
        $query->join($alias.'.gn', 'gn');
        $query->join('user.etatCivil', 'etatCivil');
        $query->leftJoin($alias.'.billet', 'billet');
        // Next may be a LEFT join because it's can be null
        // $query->join($alias.'.territoire', 'territoire');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            'user.username as username',
            'etatCivil.nom as lastname',
            'etatCivil.prenom as firstname',
            'user.email as email',
            'billet.label as billet',
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'username' => 'username',
            'email' => 'email',
            'billet_id', 'billet' => 'billet',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'username' => $this->translator->trans('Utilisateur', domain: 'repository'),
            'lastname' => $this->translator->trans('Nom', domain: 'repository'),
            'firstname' => $this->translator->trans('Prénom', domain: 'repository'),
            'email' => $this->translator->trans('Email', domain: 'repository'),
            'billet' => $this->translator->trans('Billet', domain: 'repository'),
        ];
    }
}
