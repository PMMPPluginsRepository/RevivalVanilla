<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\entity\passive;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\item\WheatSeeds;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use skh6075\revivalvanilla\entity\ai\walk\WalkEntityTrait;
use skh6075\revivalvanilla\entity\Animal;
use skh6075\revivalvanilla\world\sound\entity\LayEggSound;

class Chicken extends Animal{
	use WalkEntityTrait{
		entityBaseTick as baseTick;
	}

	private int $layDelay = 0;

	public static function getNetworkTypeId() : string{
		return EntityIds::CHICKEN;
	}

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.8, 0.6);
	}

	public function getDefaultMaxHealth() : int{
		return 4;
	}

	public function getName() : string{
		return 'Chicken';
	}

	public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
		return $target instanceof Player && $target->getInventory()->getItemInHand() instanceof WheatSeeds;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = false;
		if($this->layDelay > 0){
			$this->layDelay -= $tickDiff;
			if($this->layDelay <= 0){
				$hasUpdate = true;
				$this->getWorld()->addSound($this->getPosition(), new LayEggSound($this));
				$this->getWorld()->dropItem($this->getPosition()->add(0, 0.3, 0), VanillaItems::EGG());
			}
		}elseif(!random_int(0, 999)){
			$hasUpdate = true;
			$this->layDelay = random_int(450, 720);
		}
		return $this->baseTick($tickDiff) || $hasUpdate;
	}

	public function getDrops() : array{
		return $this->isBaby() ? [] : [
			ItemFactory::getInstance()->get($this->isOnFire() ? ItemIds::COOKED_CHICKEN : ItemIds::RAW_CHICKEN, 0, 1),
			VanillaItems::FEATHER()->setCount(random_int(0, 2))
		];
	}

	public function getXpDropAmount() : int{
		return random_int(1, 3);
	}
}