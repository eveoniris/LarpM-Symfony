<?php

namespace App\Repository;

use App\Entity\Document;
use App\Form\DocumentForm;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\DocumentRepository.
 *
 * @author kevin
 */
class DocumentRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     *
     * @return ArrayCollection $classes
     *
     * @deprecated
     */
    public function findAllOrderedByCode()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT d FROM App\Entity\Document d ORDER BY d.code ASC')
            ->getResult();
    }

    /**
     * Trouve le nombre de documents correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('d'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les documents correspondant aux critères de recherche.
     */
    public function findList($type, $value, array $order = ['by' => 'titre', 'dir' => 'ASC'], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('d.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d');
        $qb->from(\App\Entity\Document::class, 'd');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'titre':
                    $qb->andWhere('d.titre LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('d.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'code':
                    $qb->andWhere('d.code LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'auteur':
                    $qb->andWhere('d.auteur LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('d.id = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Type "%s" inconnu', $type));
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

        $query->leftJoin($alias.'.groupes', 'groupes');
        $query->leftJoin($alias.'.user', 'user');
        // $query->join($alias.'.langues', 'langue');
        // TOO heavy (use lazy load ?) $query->join($alias.'lieus', 'lieus');
        // TOO heavy (use lazy load ?) $query->join($alias.'personnages', 'personnages');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.titre', // => 'Libellé',
            $alias.'.code',
            $alias.'.description', // => 'Description',
            $alias.'.impression',
            $alias.'.cryptage',
            'user.username as user',
            $alias.'.auteur',
            // TODO add groupe, lieu personnage ?
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
            $alias.'.code' => [
                OrderBy::ASC => [$alias.'.code' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.code' => OrderBy::DESC],
            ],
            $alias.'.impression' => [
                OrderBy::ASC => [$alias.'.impression' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.impression' => OrderBy::DESC],
            ],
            $alias.'.cryptage' => [
                OrderBy::ASC => [$alias.'.cryptage' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.cryptage' => OrderBy::DESC],
            ],
            $alias.'.auteur' => [
                OrderBy::ASC => [$alias.'.auteur' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.auteur' => OrderBy::DESC],
            ],
            // using alias like "scriptwriter" require another template loop due to the added $query->select() entity
            'user.username' => [OrderBy::ASC => ['user.username' => OrderBy::ASC], OrderBy::DESC => ['user.username' => OrderBy::DESC]],
            'groupes.nom' => [OrderBy::ASC => ['groupes.nom' => OrderBy::ASC], OrderBy::DESC => ['groupes.nom' => OrderBy::DESC]],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'scriptwriter' => 'user',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'code' => $this->translator->trans('Code', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'titre' => $this->translator->trans('Titre', domain: 'repository'),
            'impression' => $this->translator->trans('Impression', domain: 'repository'),
            'cryptage' => $this->translator->trans('Chiffrement', domain: 'repository'),
            'user' => $this->translator->trans('Scénariste', domain: 'repository'),
            'auteur' => $this->translator->trans('Auteur', domain: 'repository'),
        ];
    }

    public function getPersonnages(Document $document): QueryBuilder
    {
        /** @var static $documentRepository */
        $documentRepository = $this->entityManager->getRepository(DocumentForm::class);

        return $documentRepository->createQueryBuilder('perso')
            ->innerJoin('perso.documents', 'd')
            ->where('d.id = :did')
            ->setParameter('did', $document->getId());
    }
}
