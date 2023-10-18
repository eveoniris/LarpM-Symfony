<?php

namespace App\Entity;

use App\Entity\BaseParticipantHasRestauration;

/**
 * App\Entity\ParticipantHasRestauration
 *
 */
class ParticipantHasRestauration extends BaseParticipantHasRestauration
{
	public function __construct()
	{
		$this->setDate(new \Datetime('NOW'));	
	}
}