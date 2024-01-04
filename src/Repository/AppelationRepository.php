<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\AppelationRepository.
 *
 * @author kevin
 */
class AppelationRepository extends BaseRepository
{
    public $app;

    /**
     * Fourni la liste des appelations n'étant pas dépendante d'une autre appelation.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRoot()
    {
        $query = $this->app['orm.em']->createQuery('SELECT a FROM App\Entity\Appelation a WHERE a.appelation IS NULL');

        return $query->getResult();
    }
}
