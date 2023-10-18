<?php

namespace App\Entity;

use App\Entity\BaseRenommeHistory;

/**
 * App\Entity\RenommeHistory
 *
 */
class RenommeHistory extends BaseRenommeHistory
{
	public function __construct()
	{
		$this->setDate(new \Datetime('NOW'));
	}
}