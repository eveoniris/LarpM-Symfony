<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: PostRepository::class)]
class Post extends BasePost
{
    /**
     * constructeur.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }

    /**
     * Fourni le nombre de vue de ce post.
     */
    public function getViews(): int
    {
        return $this->getPostViews()->count();
    }

    /**
     * Fourni le post initial (ou a défaut lui-même).
     */
    public function getAncestor()
    {
        if ($this->getPost()) {
            return $this->getPost()->getAncestor();
        }

        return $this;
    }

    /**
     * Fourni la dernière réponse (ou à défaut lui-même).
     */
    public function getLastPost()
    {
        if ($this->getPosts()->count() > 0) {
            return $this->getPosts()->last();
        }

        return $this;
    }

    /**
     * Fourni tous les Users ayant répondu à ce post (ainsi que l'auteur initial).
     */
    public function getWatchingUsers()
    {
        return $this->getUsers();
    }

    /**
     * Ajoute un utilisateur dans la liste des utilisateurs qui surveillent le sujet
     * Uniquement s'il n'est pas déjà dans la liste.
     *
     * @param unknown $User
     */
    public function addWatchingUser($User)
    {
        foreach ($this->getWatchingUsers() as $u) {
            if ($u == $User) {
                return $this;
            }
        }

        return $this->addUser($User);
    }

    /**
     * Retire un utilisateur de la liste des utilisateurs qui surveillent le sujet.
     *
     * @param unknown $User
     */
    public function removeWatchingUser(User $User)
    {
        return $this->removeUser($User);
    }

    /**
     * Fourni l'auteur du post.
     */
    public function getUser()
    {
        return $this->getUserRelatedByUserId();
    }

    /**
     * Met a jour l'auteur du post.
     *
     * @param unknown $User
     */
    public function setUser(User $User)
    {
        return $this->setUserRelatedByUserId($User);
    }

    /**
     * Determine si le post est un post racine.
     */
    public function isRoot(): bool
    {
        return null === $this->getPost();
    }
}
