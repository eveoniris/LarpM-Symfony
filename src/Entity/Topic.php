<?php


namespace App\Entity;

use App\Repository\TopicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TopicRepository::class)]
class Topic extends BaseTopic implements \Stringable
{
    /**
     * Constructeur.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }

    /**
     * Affichage d'un topic.
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Retourne la liste des topics classé par la date de publication de leurs posts.
     */
    public function getTopicsOrderByLastPost($app = null): Collection
    {
        $topics = $this->getTopics();
        $iterator = $topics->getIterator();

        $iterator->uasort(static function ($first, $second) use ($app) {
            if ($first === $second) {
                return 0;
            }

            $firstLastPost = $first->getLastPost($app);
            $secondLastPost = $second->getLastPost($app);
            if ($firstLastPost && $secondLastPost) {
                return (float) $firstLastPost->getCreationDate()->format('U.u') > (float) $secondLastPost->getCreationDate()->format('U.u') ? -1 : 1;
            } elseif ($firstLastPost) {
                return -1;
            } else {
                return 1;
            }
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * Fourni la liste des posts d'un topic classé par date de publication (en prennant en compte les réponses).
     */
    public function getPostsOrderByDate(): Collection
    {
        $posts = $this->getPosts();
        $iterator = $posts->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            if ($first === $second) {
                return 0;
            }

            $first = $first->getLastPost();
            $second = $second->getLastPost();

            return (float) $first->getCreationDate()->format('U.u') > (float) $second->getCreationDate()->format('U.u') ? -1 : 1;
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * Fourni le dernier post d'un topic (en recherchant dans les sous-topics).
     */
    public function getLastPost(array $app = null)
    {
        $lastPost = null;

        foreach ($this->getPosts() as $post) {
            $postChecked = $post->getLastPost();

            if ($lastPost) {
                if ($postChecked->getCreationDate() > $lastPost->getCreationDate()) {
                    $lastPost = $postChecked;
                }
            } else {
                $lastPost = $postChecked->getLastPost();
            }
        }

        foreach ($this->getTopics() as $topic) {
            if (null != $app && !$app['security']->isGranted('TOPIC_RIGHT', $topic)) {
                continue;
            }

            $topicLastPost = $topic->getLastPost($app);

            if ($lastPost && $topicLastPost) {
                if ($topicLastPost->getCreationDate() > $lastPost->getCreationDate()) {
                    $lastPost = $topicLastPost;
                }
            } elseif ($topicLastPost) {
                $lastPost = $topicLastPost;
            }
        }

        return $lastPost;
    }

    /**
     * Fourni la liste de tous les ancêtres d'un topic.
     *
     * @param unknown $array
     */
    public function getAncestor(array $array = [])
    {
        if ($this->getTopic()) {
            $array = $this->getTopic()->getAncestor($array);
            $array[] = $this->getTopic();
        }

        return $array;
    }

    /**
     * Fourni le nombre de post dans ce topic et ces descendants.
     */
    public function getPostCount()
    {
        $count = $this->getPosts()->count();
        foreach ($this->getTopics() as $topic) {
            $count += $topic->getPostCount();
        }

        return $count;
    }
}
