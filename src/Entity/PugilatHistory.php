<?php

namespace App\Entity;

use App\Entity\BasePugilatHistory;

/**
 * App\Entity\PugilatHistory
 *
 */
class PugilatHistory extends BasePugilatHistory
{
	public function __construct()
	{
		$this->setDate(new \Datetime('NOW'));
	}
}