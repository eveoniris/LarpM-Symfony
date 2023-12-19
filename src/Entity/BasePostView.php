<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'post_view')]
#[ORM\Index(columns: ['post_id'], name: '"fk_post_view_post1_idx"')]
#[ORM\Index(columns: ['user_id'], name: 'fk_post_view_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePostView', 'extended' => 'PostView'])]
abstract class BasePostView
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $date;

    #[ManyToOne(targetEntity: Post::class, inversedBy: 'postViews')]
    #[JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: 'false')]
    protected Post $post;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'postViews')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PostView
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\PostView
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     *
     * @return \DateTime
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * Set Post entity (many to one).
     *
     * @return \App\Entity\PostView
     */
    public function setPost(Post $post = null): static
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get Post entity (many to one).
     *
     * @return \App\Entity\Post
     */
    public function getPost(): ?Post
    {
        return $this->post;
    }

    /**
     * Set User entity (many to one).
     *
     * @return \App\Entity\PostView
     */
    public function setUser(User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /* public function __sleep()
    {
        return ['id', 'date', 'post_id', 'user_id'];
    } */
}
