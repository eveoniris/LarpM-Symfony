<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'topic')]
#[ORM\Index(columns: ['topic_id'], name: 'fk_topic_topic1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_topic_ser1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTopic', 'extended' => 'Topic'])]
abstract class BaseTopic
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 450)]
    protected string $title = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[Column(name: 'right', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $right = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $object_id = null;

    #[Column(name: 'key', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $key = null;

    #[OneToMany(mappedBy: 'topic', targetEntity: Gn::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $gns;

    #[OneToMany(mappedBy: 'topic', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $groupes;

    #[OneToMany(mappedBy: 'topic', targetEntity: Post::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $posts;

    #[OneToMany(mappedBy: 'topic', targetEntity: Religion::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $religions;

    #[OneToMany(mappedBy: 'topic', targetEntity: SecondaryGroup::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $secondaryGroups;

    #[OneToMany(mappedBy: 'topic', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'topic_id', nullable: 'false')]
    protected Collection $territoires;

    #[OneToMany(mappedBy: 'topic', targetEntity: Topic::class)]
    #[JoinColumn(name: 'topic_id', referencedColumnName: 'billet_id', nullable: 'false')]
    protected Collection $topics;

    #[ManyToOne(targetEntity: Topic::class, inversedBy: 'topics')]
    #[JoinColumn(name: 'topic_id', referencedColumnName: 'id', nullable: 'false')]
    protected Topic $topic;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'topics')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected User $user;

    public function __construct()
    {
        $this->gns = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->religions = new ArrayCollection();
        $this->secondaryGroups = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->topics = new ArrayCollection();
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
        return $this->title;
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
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
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
     */
    public function setUpdateDate(\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get the value of update_date.
     */
    public function getUpdateDate(): \DateTime
    {
        return $this->update_date;
    }

    /**
     * Set the value of right.
     */
    public function setRight(string $right): static
    {
        $this->right = $right;

        return $this;
    }

    /**
     * Get the value of right.
     */
    public function getRight(): string
    {
        return $this->right ?? '';
    }

    /**
     * Set the value of object_id.
     */
    public function setObjectId(int $object_id): static
    {
        $this->object_id = $object_id;

        return $this;
    }

    /**
     * Get the value of object_id.
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    /**
     * Set the value of key.
     */
    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the value of key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Add Gn entity to collection (one to many).
     */
    public function addGn(Gn $gn): static
    {
        $this->gns[] = $gn;

        return $this;
    }

    /**
     * Remove Gn entity from collection (one to many).
     */
    public function removeGn(Gn $gn): static
    {
        $this->gns->removeElement($gn);

        return $this;
    }

    /**
     * Get Gn entity collection (one to many).
     */
    public function getGns(): Collection
    {
        return $this->gns;
    }

    /**
     * Add Groupe entity to collection (one to many).
     */
    public function addGroupe(Groupe $groupe): static
    {
        $this->groupes[] = $groupe;

        return $this;
    }

    /**
     * Remove Groupe entity from collection (one to many).
     */
    public function removeGroupe(Groupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity collection (one to many).
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
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
     * Add Religion entity to collection (one to many).
     */
    public function addReligion(Religion $religion): static
    {
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Remove Religion entity from collection (one to many).
     */
    public function removeReligion(Religion $religion): static
    {
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Get Religion entity collection (one to many).
     */
    public function getReligions(): Collection
    {
        return $this->religions;
    }

    /**
     * Add SecondaryGroup entity to collection (one to many).
     */
    public function addSecondaryGroup(SecondaryGroup $secondaryGroup): static
    {
        $this->secondaryGroups[] = $secondaryGroup;

        return $this;
    }

    /**
     * Remove SecondaryGroup entity from collection (one to many).
     */
    public function removeSecondaryGroup(SecondaryGroup $secondaryGroup): static
    {
        $this->secondaryGroups->removeElement($secondaryGroup);

        return $this;
    }

    /**
     * Get SecondaryGroup entity collection (one to many).
     */
    public function getSecondaryGroups(): Collection
    {
        return $this->secondaryGroups;
    }

    /**
     * Add Territoire entity to collection (one to many).
     */
    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     */
    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     */
    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    /**
     * Add Topic entity to collection (one to many).
     */
    public function addTopic(Topic $topic): static
    {
        $this->topics[] = $topic;

        return $this;
    }

    /**
     * Remove Topic entity from collection (one to many).
     */
    public function removeTopic(Topic $topic): static
    {
        $this->topics->removeElement($topic);

        return $this;
    }

    /**
     * Get Topic entity collection (one to many).
     */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    /**
     * Set Topic entity (many to one).
     */
    public function setTopic(Topic $topic = null): Collection
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     */
    public function getTopic(): static
    {
        return $this->topic;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(User $User = null): static
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /*public function __sleep()
    {
        return ['id', 'title', 'description', 'creation_date', 'update_date', 'topic_id', 'User_id', 'right', 'object_id', 'key'];
    }*/
}
