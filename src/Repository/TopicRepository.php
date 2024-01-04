<?php


namespace App\Repository;

use App\Entity\Topic;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method Topic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Topic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Topic[]    findAll()
 * @method int        count()
 * @method Topic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TopicRepository extends BaseRepository
{
    /**
     * Trouve tous les topics de premier niveau (qui ne sont pas des sous-forums).
     */
    public function findAllRoot()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Topic t WHERE IDENTITY(t.topic) IS NULL ORDER BY t.creation_date ASC');

        return $query->getResult();
    }

    /**
     * Trouve tous les topics classés par date de création.
     * Si le topicId est fourni, retourne la liste des topics appartenant à ce topic.
     * Si le topicId n'est pas fourni, retourne la liste des topics de premier niveau.
     *
     * @param int $topicId
     *
     * @return ArrayCollection $topics
     */
    public function findAllOrderedByCreationDate($topicId = null)
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Topic t WHERE t.topic_id?1 ORDER BY t.creation_date ASC');
        if (null === $topicId) {
            $query->setParameter(1, 'IS NULL');
        } else {
            $query->setParameters(new ArrayCollection(
                [new Parameter(1, '=?2'),
                    new Parameter(2, $topicId)]
            ));
        }

        return $query->getResult();
    }

    /**
     * Trouve tous les topics correspondant aux gns dans lequel est inscrit le joueur.
     *
     * @param int $joueurId
     */
    public function findAllRelatedToJoueurReferedGns($joueurId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Topic t WHERE t.id IN ( SELECT IDENTITY(g.topic) FROM App\Entity\Gn g WHERE g.actif = true AND IDENTITY(t.topic) IS NULL AND g.id IN (SELECT IDENTITY(jg.gn) FROM App\Entity\JoueurGn jg WHERE IDENTITY(jg.joueur) = :joueurId))')
            ->setParameter('joueurId', $joueurId)
            ->getResult();
    }

    /**
     * Trouve tous les topics correspondant aux groupes dans lequel est inscrit le joueur.
     *
     * @param int $joueurId
     */
    public function findAllRelatedToJoueurReferedGroupes($joueurId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Topic t JOIN t.groupe g JOIN g.joueurs j WHERE j.id = :joueurId AND t.topic_id IS NULL')
            ->setParameter('joueurId', $joueurId)
            ->getResult();
    }
}
