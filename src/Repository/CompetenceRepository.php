<?php


namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CompetenceFamily;

/**
 * LarpManager\Repository\CompetenceRepository.
 *
 * @author kevin
 */
class CompetenceRepository extends BaseRepository
{
    /**
     * Find all competences ordered by label.
     *
     * @return ArrayCollection $competences
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Competence c JOIN c.competenceFamily cf JOIN c.level l ORDER BY cf.label ASC, l.index ASC')
            ->getResult();
    }

    /**
     * Returns a query builder to find all competences ordered by label.
     */
    public function getQueryBuilderFindAllOrderedByLabel(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(\App\Entity\Competence::class, 'c')
            ->join('c.competenceFamily', 'cf')
            ->join('c.level', 'l')
            ->addOrderBy('cf.label')
            ->addOrderBy('l.index');
    }

    /**
	 * Fourni la liste des compétences de premier niveau
	 */
	public function getRootCompetences(EntityManagerInterface $entityManager): ArrayCollection
	{
		$rootCompetences = new ArrayCollection();
	
		$repo = $entityManager->getRepository(CompetenceFamily::class);
		$competenceFamilies = $repo->findAll();
	
		foreach ( $competenceFamilies as $competenceFamily)
		{
			$competence = $competenceFamily->getFirstCompetence();
			if ( $competence )
			{
				$rootCompetences->add($competence);
			}
		}
		
		// trie des competences disponibles
		$iterator = $rootCompetences->getIterator();
		$iterator->uasort(function ($a, $b) {
			return ($a->getLabel() < $b->getLabel()) ? -1 : 1;
		});
		
		return  new ArrayCollection(iterator_to_array($iterator));
	}
}
