<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\inventory\entity;

use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use skh6075\revivalvanilla\entity\LivingBase;

class EntityInventory extends SimpleInventory{

	public function __construct(private LivingBase $holder){
		parent::__construct(1);
	}

	public function getItemInHand() : Item{
		return $this->getItem(0);
	}

	public function setItemInHand(Item $item) : void{
		$this->setItem(0, $item);
	}

	public function getHolder() : LivingBase{
		return $this->holder;
	}
}