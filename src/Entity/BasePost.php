<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
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

    /**
     * @Column(type="string", length=450)
     */
    protected $title;

    /**
     * @Column(name="`text`", type="text")
     */
    protected $text;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $creation_date;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $update_date;

    /**
     * @OneToMany(targetEntity="Post", mappedBy="post")
     *
     * @JoinColumn(name="id", referencedColumnName="post_id", nullable=false)
     */
    protected $posts;

    /**
     * @OneToMany(targetEntity="PostView", mappedBy="post")
     *
     * @JoinColumn(name="id", referencedColumnName="post_id", nullable=false)
     */
    protected $postViews;

    /**
     * @ManyToOne(targetEntity="Topic", inversedBy="posts")
     *
     * @JoinColumn(name="topic_id", referencedColumnName="id")
     */
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
    protected ArrayCollection $users;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->postViews = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Post
     */
    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of title.
     *
     * @param string $title
     *
     * @return \App\Entity\Post
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of text.
     *
     * @param string $text
     *
     * @return \App\Entity\Post
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return \App\Entity\Post
     */
    public function setCreationDate($creation_date)
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
     *
     * @param \DateTime $update_date
     *
     * @return \App\Entity\Post
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get the value of update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Add Post entity to collection (one to many).
     *
     * @return \App\Entity\Post
     */
    public function addPost(Post $post)
    {
        $this->posts[] = $post;

        return $this;
    }

    /**
     * Remove Post entity from collection (one to many).
     *
     * @return \App\Entity\Post
     */
    public function removePost(Post $post)
    {
        $this->posts->removeElement($post);

        return $this;
    }

    /**
     * Get Post entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * Add PostView entity to collection (one to many).
     *
     * @return \App\Entity\Post
     */
    public function addPostView(PostView $postView)
    {
        $this->postViews[] = $postView;

        return $this;
    }

    /**
     * Remove PostView entity from collection (one to many).
     *
     * @return \App\Entity\Post
     */
    public function removePostView(PostView $postView)
    {
        $this->postViews->removeElement($postView);

        return $this;
    }

    /**
     * Get PostView entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPostViews()
    {
        return $this->postViews;
    }

    /**
     * Set Topic entity (many to one).
     *
     * @return \App\Entity\Post
     */
    public function setTopic(Topic $topic = null)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     *
     * @return \App\Entity\Topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set User entity related by `User_id` (many to one).
     *
     * @return \App\Entity\Post
     */
    public function setUserRelatedByUserId(User $User = null)
    {
        $this->UserRelatedByUserId = $User;

        return $this;
    }

    /**
     * Get User entity related by `User_id` (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUserRelatedByUserId()
    {
        return $this->UserRelatedByUserId;
    }

    /**
     * Set Post entity (many to one).
     *
     * @return \App\Entity\Post
     */
    public function setPost(Post $post = null)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get Post entity (many to one).
     *
     * @return \App\Entity\Post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Add User entity to collection.
     *
     * @return \App\Entity\Post
     */
    public function addUser(User $User)
    {
        $User->addPost($this);
        $this->Users[] = $User;

        return $this;
    }

    /**
     * Remove User entity from collection.
     *
     * @return \App\Entity\Post
     */
    public function removeUser(User $User)
    {
        $User->removePost($this);
        $this->Users->removeElement($User);

        return $this;
    }

    /**
     * Get User entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->Users;
    }

    public function __sleep()
    {
        return ['id', 'title', 'text', 'creation_date', 'update_date', 'topic_id', 'User_id', 'post_id'];
    }
}
