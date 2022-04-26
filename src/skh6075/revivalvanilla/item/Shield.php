<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\item;

use pocketmine\item\Durable;

class Shield extends Durable{

	public function getMaxDurability() : int{
		return 336;
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}