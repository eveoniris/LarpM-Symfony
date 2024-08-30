<?php

namespace App\Repository;

use App\Entity\Groupe;
use App\Entity\Territoire;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class GroupeRepository extends BaseRepository
{
    /**
     * Trouve tous les groupes classé par ordre alphabétique.
     */
    public function findAll(): array
    {
        return $this->findBy([], ['nom' => 'ASC']);
    }

    /**
     * Trouve tous les groupes de PJ classé par numéro.
     */
    public function findAllPj()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g WHERE g.pj = true ORDER BY g.numero ASC')
            ->getResult();
    }

    /**
     * Recherche d'une liste de groupe.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('g');
        $qb->from(Groupe::class, 'g');

        if ($type && $value) {
            switch ($type) {
                case 'numero':
                    $qb->andWhere('g.numero = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('g.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Trouve les annonces correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(Groupe::class, 'g');

        if ($type && $value) {
            switch ($type) {
                case 'numero':
                    $qb->andWhere('g.numero = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Fourni la liste de tous les groupes classé par numéro.
     *
     * @return Collection App\Entity\Groupe $groupes
     */
    public function findAllOrderByNumero()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g ORDER BY g.numero ASC')
            ->getResult();
    }

    /**
     * Trouve un groupe en fonction de son code.
     *
     * @param string $code
     *
     * @return App\Entity\Groupe $groupe
     */
    public function findOneByCode($code)
    {
        $groupes = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g WHERE g.code = :code')
            ->setParameter('code', $code)
            ->getResult();

        return reset($groupes);
    }

    public function findByGn(int $gn, ?string $type = '', ?string $value = '', ?array $order = [])
    {
        // Liste des groupes du GN en paramètre
        $qbGroupes = $this->getEntityManager()
            ->createQuery('SELECT IDENTITY(g.groupe) FROM App\Entity\GroupeGn g WHERE g.gn = :code')
            ->setParameter('code', $gn)
            ->getResult();
        $listeGroupes = array_column($qbGroupes, 1);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t');
        $qb->from(Territoire::class, 't');
        $qb->andWhere($qb->expr()->in('t.groupe', $listeGroupes));
        $qb->orderBy('t.'.$order['by'], $order['dir']);

        return $qb->getQuery();
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
        $query->join($alias.'.userRelatedByScenaristeId', 'scenariste');
        $query->join('scenariste.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.numero',
            $alias.'.nom', // => 'Libellé',
            'scenariste.username as scenariste_username',
            'etatCivil.nom as scenariste_nom',
            'etatCivil.prenom as scenariste_prenom',
            'scenariste.email as scenariste_email',
            $alias.'.description', // => 'Description',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'nom' => [
                OrderBy::ASC => [$alias.'.nom' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.nom' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'numero' => [
                OrderBy::ASC => [$alias.'.numero' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.numero' => OrderBy::DESC],
            ],
            'scenariste' => [
                OrderBy::ASC => ['scenariste.username' => OrderBy::ASC],
                OrderBy::DESC => ['scenariste.username' => OrderBy::DESC],
            ],
            'scenariste_nom' => [
                OrderBy::ASC => ['etatCivil.nom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.nom' => OrderBy::DESC],
            ],
            'scenariste_prenom' => [
                OrderBy::ASC => ['etatCivil.prenom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.prenom' => OrderBy::DESC],
            ],
            'scenariste_email' => [
                OrderBy::ASC => ['scenariste.email' => OrderBy::ASC],
                OrderBy::DESC => ['scenariste.email' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'scenariste_id', 'scenariste', 'username' => 'scenariste',
            'etat_civil_id', 'etatCivil_id', 'etatCivil' => 'etatCivil',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'nom' => $this->translator->trans('Nom', domain: 'repository'),
            'numero' => $this->translator->trans('Numero', domain: 'repository'),
            'scenariste_username' => $this->translator->trans('Scénariste', domain: 'repository'),
            'scenariste_email' => $this->translator->trans('Email scénariste', domain: 'repository'),
            'scenariste_prenom' => $this->translator->trans('Prénom scénariste', domain: 'repository'),
            'scenariste_nom' => $this->translator->trans('Nom scénariste', domain: 'repository'),
        ];
    }
}
