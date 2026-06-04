<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FicheRetourGroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: FicheRetourGroupeRepository::class)]
class FicheRetourGroupe extends BaseFicheRetourGroupe
{
    /** @var Collection<int, FicheRetourGroupeHistory> */
    #[\Doctrine\ORM\Mapping\OneToMany(mappedBy: 'ficheRetourGroupe', targetEntity: FicheRetourGroupeHistory::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[\Doctrine\ORM\Mapping\OrderBy(['action_date' => 'DESC'])]
    private Collection $histories;

    public function __construct()
    {
        parent::__construct();
        $this->histories = new ArrayCollection();
    }

    /** @return Collection<int, FicheRetourGroupeHistory> */
    public function getHistories(): Collection
    {
        return $this->histories;
    }

    public function addHistory(FicheRetourGroupeHistory $history): static
    {
        $history->setFicheRetourGroupe($this);
        $this->histories->add($history);

        return $this;
    }
}
