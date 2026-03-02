<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Reponse extends BaseReponse
{
    public function setReponse(string $reponse): static
    {
        $hash = sha1($reponse);
        parent::setReponse($hash);

        return $this;
    }
}
