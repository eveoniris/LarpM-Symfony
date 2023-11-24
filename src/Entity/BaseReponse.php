<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Mime\Encoder\QpEncoder;

#[ORM\Entity]
#[ORM\Table(name: 'billet')]
#[ORM\Index(columns: ['question_id'], name: 'fk_reponse_idx')]
#[ORM\Index(columns: ['participant_id'], name: 'fk_reponse_participant1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseReponse', 'extended' => 'Reponse'])]
abstract class BaseReponse
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $reponse;

    #[ManyToOne(targetEntity: Question::class, inversedBy: 'reponses')]
    #[JoinColumn(name: 'question_id', referencedColumnName: 'id', nullable: 'false')]
    protected Question $question;

    #[ManyToOne(targetEntity: Participant::class, inversedBy: 'reponses')]
    #[JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: 'false')]
    protected Participant $participant;

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
     * Set the value of reponse.
     */
    public function setReponse(string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    /**
     * Get the value of reponse.
     */
    public function getReponse(): string
    {
        return $this->reponse ?? '';
    }

    /**
     * Set Question entity (many to one).
     */
    public function setQuestion(Question $question = null): static
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get Question entity (many to one).
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    /**
     * Set Participant entity (many to one).
     */
    public function setParticipant(Participant $participant = null): static
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     */
    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    public function __sleep()
    {
        return ['id', 'question_id', 'reponse', 'participant_id'];
    }
}
