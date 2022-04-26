<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Transparent;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;
use pocketmine\world\World;
use skh6075\revivalvanilla\Loader;

class Scaffolding extends Transparent{

	/**
	 * @phpstan-var array<int, TaskHandler>
	 * @var TaskHandler[]
	 */
	private array $taskHandler = [];

	public function onNearbyBlockChange() : void{
		$down = $this->getSide(Facing::DOWN);
		if($down->isTransparent() && !$down->isSameType($this)){
			$this->position->getWorld()->useBreakOn($this->position);
		}
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null && $item->equals($this->asItem(), false, false)){
			$nowY = $this->position->getFloorY();
			for($y = $nowY, $checkY = 0; $y < World::Y_MAX; $y++){
				$checkY++;
				$block = $player->getWorld()->getBlock($this->position->add(0, $checkY, 0));
				if($block->isSameType(VanillaBlocks::AIR())){
					break;
				}
			}
			if($checkY < World::Y_MAX){
				$this->position->getWorld()->setBlock($this->position->add(0, $checkY, 0), (clone $this));
				$item->pop();
			}
		}
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		if(!$entity instanceof Player){
			return false;
		}
		if(isset($this->taskHandler[$entity->getId()])){
			return true;
		}
		/** @var Player $entity */
		$handler = Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($entity): void{
			$location = $entity->getLocation();
			$block = $entity->getWorld()->getBlockAt($location->getFloorX(), (int) ($location->getFloorY() + $entity->getEyeHeight()), $location->getFloorZ());
			if(!$block->isSameType($this)){
				if(isset($this->taskHandler[$entity->getId()])){
					$this->taskHandler[$entity->getId()]->cancel();
					unset($this->taskHandler[$entity->getId()]);
				}
				$entity->setHasBlockCollision(false);
				return;
			}
			if($entity->isSneaking()){
				$entity->setHasBlockCollision(true);
				$entity->teleport(Position::fromObject($entity->getPosition()->add(0, -1, 0), $entity->getWorld())); //TODO..
			}
		}), 15);
		$this->taskHandler[$entity->getId()] = $handler;
		return true;
	}
}