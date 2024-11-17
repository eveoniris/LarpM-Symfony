<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageLignee extends BasePersonnageLignee
{
    /**
     * Fourni la liste des descendants directs.
     */
    public function getDescendants(): null
    {
        // TODO: getDescendants

        return null;
    }

    public function getLabel(): ?string
    {
        return $this->getLignee()?->getLabel() ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->getLignee()?->getDescription() ?? '';
    }

    /**
     * Trouve tous les descendants correspondant au personnage.
     *
     * @return ArrayCollection $classes
     */
    public function getDescendantsByPersonnage($personnage_id)
    {
        $qb = $this->createQueryBuilder();

        $qb->select('d');
        $qb->from(PersonnageChronologie::class, 'd');
        $qb->join('d.personnage', 'p');
        $qb->where('(d.parent1 = :personnage1 OR d.parent2 = :personnage2)');
        $qb->orderBy('d.id', 'DESC');
        $qb->setParameter('personnage1', $personnage_id);
        $qb->setParameter('personnage2', $personnage_id);

        return $qb->getQuery()->getResult();
    }
}
