<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Opaque;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Shovel;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use skh6075\revivalvanilla\block\tile\CampfireTile;
use skh6075\revivalvanilla\block\utils\HorizontalBlockTrait;

class Campfire extends Opaque{
	use HorizontalBlockTrait {
		writeStateToMeta as writeFacingToMeta;
		readStateFromData as readFacingFromData;
	}

	protected bool $extinguish = false;

	protected function getExtinguishMetaShift() : int{
		return 2; //default
	}

	protected function readExtinguishFromMeta(int $meta) : void{
		$this->setExtinguish((bool) ($meta >> $this->getExtinguishMetaShift()));
	}

	protected function writeExtinguishToMeta() : int{
		return $this->getExtinguish() << $this->getExtinguishMetaShift();
	}

	protected function writeStateToMeta() : int{
		return $this->writeExtinguishToMeta() | $this->writeFacingToMeta();
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->readExtinguishFromMeta($stateMeta);
		$this->readFacingFromData($id, $stateMeta);
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	/** @return AxisAlignedBB[] */
	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 0.5)];
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($face === Facing::UP && $item instanceof Shovel){
			$block = clone $this;
			$block->setExtinguish(!$block->getExtinguish());
			$this->position->getWorld()->setBlock($this->position, $block);
			return true;
		}
		if($player !== null){
			$tile = $this->position->getWorld()->getTile($this->position);
			if($tile instanceof CampfireTile && $tile->addItem($item)){
				$item->pop();
				$this->position->getWorld()->setBlock($this->position, $this);
			}
			return true;
		}
		return false;
	}

	public function onEntityInside(Entity $entity) : bool{
		if(
			!$this->extinguish ||
			($entity instanceof Player && !$entity->hasFiniteResources())
		){
			return false;
		}
		$entity->setOnFire(8);
		$entity->attack(new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1));
		return true;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function getExtinguish() : bool{
		return $this->extinguish;
	}

	public function setExtinguish(bool $extinguish) : self{
		$this->extinguish = $extinguish;
		return $this;
	}

	public function onScheduledUpdate() : void{
		$tile = $this->position->getWorld()->getTile($this->position);
		if(!$tile instanceof CampfireTile || $tile->isClosed()){
			return;
		}

		$canChange = false;
		foreach($tile->getContents() as $slot => $item){
			$tile->increaseSlotTime($slot);
			if($tile->getItemTime($slot) < 30){
				continue;
			}
			$tile->setItem(ItemFactory::air(), $slot);
			$tile->setSlotTime($slot, 0);

			$this->position->getWorld()->dropItem(
				source: $this->position->add(0, 1, 0),
				item: ItemFactory::getInstance()->get(CampfireTile::RECIPES[$item->getId()] ?? $item->getId())
			);
			$canChange = true;
		}
		if($canChange){
			$this->position->getWorld()->setBlock($this->position, $this);
		}
		$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 20);
	}
}