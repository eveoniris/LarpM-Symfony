<?php

namespace App\Entity;

use App\Entity\BaseRelecture;

/**
 * App\Entity\Relecture
 *
 */
class Relecture extends BaseRelecture
{
	public function __construct()
	{
		$this->setDate(new \Datetime('NOW'));
		parent::__construct();
	}
}