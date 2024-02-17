<?php

namespace App\Form\Entity;

use App\Entity\Rangement;
use App\Entity\Tag;

class ObjetSearch extends ListSearch
{
    protected ?Tag $tag = null;
    protected ?Rangement $rangement = null;

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function getRangement(): ?Rangement
    {
        return $this->rangement;
    }

    public function setRangement(Rangement $rangement): void
    {
        $this->rangement = $rangement;
    }
}
