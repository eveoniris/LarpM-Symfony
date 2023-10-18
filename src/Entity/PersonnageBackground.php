<?php

namespace App\Entity;

use App\Entity\BasePersonnageBackground;

/**
 * App\Entity\PersonnageBackground
 *
 */
class PersonnageBackground extends BasePersonnageBackground
{
	public function __construct()
	{
		$this->setCreationDate(new \Datetime('NOW'));
		$this->setUpdateDate(new \Datetime('NOW'));
	}
}