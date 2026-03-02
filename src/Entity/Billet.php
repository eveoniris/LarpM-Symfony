<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BilletRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: BilletRepository::class)]
class Billet extends BaseBillet implements Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
        $this->setFedegn(false);
    }

    public function __toString(): string
    {
        return $this->getGn()?->getLabel() . ' - ' . $this->getLabel();
    }

    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        $result = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            $result->add($participant->getUser());
        }

        return $result;
    }

    public function setCreateur(User $User): self
    {
        $this->setUser($User);

        return $this;
    }

    public function getCreateur(): ?User
    {
        return $this->getUser();
    }

    public function fullLabel(): string
    {
        return $this->getGn()?->getLabel() . ' - ' . $this->getLabel();
    }

    public function isPnj(): bool
    {
        return stripos($this->getLabel(), 'PNJ') > 0;
    }
}
