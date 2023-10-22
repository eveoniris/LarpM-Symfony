<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Reponse extends BaseReponse
{
    public function setReponse($reponse): static
    {
        $hash = sha1($reponse);
        parent::setReponse($hash);

        return $this;
    }
}
