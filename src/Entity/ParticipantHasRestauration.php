<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class ParticipantHasRestauration extends BaseParticipantHasRestauration
{
    public function __construct()
    {
        parent::__construct();
        $this->setDate(new DateTime('NOW'));
    }
}
