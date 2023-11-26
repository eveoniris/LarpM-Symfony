<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'post')]
#[ORM\Index(columns: ['topic_id'], name: 'fk_post_topic1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_post_user1_idx')]
#[ORM\Index(columns: ['post_id'], name: 'fk_post_post1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePost', 'extended' => 'Post'])] class BasePost
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 450)]
    protected string $title;

    #[Column(name: 'text', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $text;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date;

    #[OneToMany(mappedBy: 'post', targetEntity: Post::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    protected Collection $posts;

    #[OneToMany(mappedBy: 'post', targetEntity: PostView::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'post_id', nullable: false)]
    protected Collection $postViews;

    #[ManyToOne(targetEntity: Topic::class, inversedBy: 'posts')]
    #[JoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    protected $topic;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'postRelatedByUserIds')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $userRelatedByUserId;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    protected ?Post $post = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $users;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->postViews = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of title.
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of title.
     */
    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * Set the value of text.
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     */
    public function getText(): string
    {
        return $this->text ?? '';
    }

    /**
     * Set the value of creation_date.
     */
    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
     */
    public function setUpdateDate(\DateTime $update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get the value of update_date.
     */
    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    /**
     * Add Post entity to collection (one to many).
     */
    public function addPost(Post $post): static
    {
        $this->posts[] = $post;

        return $this;
    }

    /**
     * Remove Post entity from collection (one to many).
     */
    public function removePost(Post $post): static
    {
        $this->posts->removeElement($post);

        return $this;
    }

    /**
     * Get Post entity collection (one to many).
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Add PostView entity to collection (one to many).
     */
    public function addPostView(PostView $postView): static
    {
        $this->postViews[] = $postView;

        return $this;
    }

    /**
     * Remove PostView entity from collection (one to many).
     */
    public function removePostView(PostView $postView): static
    {
        $this->postViews->removeElement($postView);

        return $this;
    }

    /**
     * Get PostView entity collection (one to many).
     */
    public function getPostViews(): Collection
    {
        return $this->postViews;
    }

    /**
     * Set Topic entity (many to one).
     */
    public function setTopic(Topic $topic = null): static
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     */
    public function getTopic(): Topic
    {
        return $this->topic;
    }

    /**
     * Set User entity related by `User_id` (many to one).
     */
    public function setUserRelatedByUserId(User $user = null): static
    {
        $this->userRelatedByUserId = $user;

        return $this;
    }

    /**
     * Get User entity related by `User_id` (many to one).
     */
    public function getUserRelatedByUserId(): ?User
    {
        return $this->userRelatedByUserId;
    }

    /**
     * Set Post entity (many to one).
     */
    public function setPost(Post $post = null): static
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get Post entity (many to one).
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * Add User entity to collection.
     */
    public function addUser(User $user): static
    {
        $user->addPost($this);
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove User entity from collection.
     */
    public function removeUser(User $user): static
    {
        $user->removePost($this);
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Get User entity collection.
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function __sleep()
    {
        return ['id', 'title', 'text', 'creation_date', 'update_date', 'topic_id', 'User_id', 'post_id'];
    }
}
