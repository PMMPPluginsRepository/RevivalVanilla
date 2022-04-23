<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Opaque;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\Shovel;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\player\Player;
use pocketmine\world\sound\FireExtinguishSound;
use skh6075\revivalvanilla\block\utils\HorizontalBlockTrait;

class Campfire extends Opaque{
	use HorizontalBlockTrait {
		writeStateToMeta as writeFacingToMeta;
		readStateFromData as readFacingFromData;
	}

	private const EXTINGUISH_META = 2;

	/** ignition state */
	private bool $extinguish = false;

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->extinguish = (bool) ($stateMeta >> self::EXTINGUISH_META);
		$this->readFacingFromData($id, $stateMeta);
	}

	public function setExtinguish(bool $extinguish) : void{
		$this->extinguish = $extinguish;
	}

	public function getExtinguish() : bool{
		return $this->extinguish;
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	protected function writeStateToMeta() : int{
		return ($this->extinguish << self::EXTINGUISH_META) | $this->writeFacingToMeta();
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 0.5)];
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($face === Facing::UP && $item instanceof Shovel){
			$block = clone $this;
			if($player !== null && $block->getExtinguish()){
				$this->position->getWorld()->broadcastPacketToViewers($this->position, ActorEventPacket::create($player->getId(), ActorEvent::ARM_SWING, 0));
				$this->position->getWorld()->addSound($this->position, new FireExtinguishSound());
			}
			$block->setExtinguish(!$this->getExtinguish());
			$this->position->getWorld()->setBlock($this->position, $block);
		}
		return parent::onInteract($item, $face, $clickVector, $player);
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		if($this->extinguish || ($entity instanceof Player && !$entity->hasFiniteResources())){
			return false;
		}
		$entity->setOnFire(4);
		$entity->attack(new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1.0));
		return true;
	}
}