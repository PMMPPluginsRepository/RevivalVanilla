<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Opaque;
use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use skh6075\revivalvanilla\world\sound\block\ComposterFillSound;
use skh6075\revivalvanilla\world\sound\block\ComposterFillSuccessSound;
use skh6075\revivalvanilla\world\sound\block\ComposterReadySound;

class Composter extends Opaque{

	private int $fill = 0;

	/**
	 * @phpstan-var array<int, int>
	 * @var int[]
	 */
	protected array $ingredients = [
		ItemIds::NETHER_WART => 30,
		ItemIds::GRASS => 30,
		ItemIds::KELP => 30,
		ItemIds::LEAVES => 30,
		ItemIds::DRIED_KELP => 30,
		ItemIds::BEETROOT_SEEDS => 30,
		ItemIds::MELON_SEEDS => 30,
		ItemIds::SEEDS => 30,
		ItemIds::PUMPKIN_SEEDS => 30,
		ItemIds::TALLGRASS => 30,
		ItemIds::SEAGRASS => 30,
		ItemIds::DRIED_KELP_BLOCK => 50,
		ItemIds::CACTUS => 50,
		ItemIds::MELON => 50,
		ItemIds::SUGARCANE => 50,
		ItemIds::MELON_BLOCK => 65,
		ItemIds::MUSHROOM_STEW => 65,
		ItemIds::POTATO => 65,
		ItemIds::WATER_LILY => 65,
		ItemIds::CARROT => 65,
		ItemIds::SEA_PICKLE => 65,
		ItemIds::BROWN_MUSHROOM_BLOCK => 65,
		ItemIds::RED_MUSHROOM_BLOCK => 65,
		ItemIds::WHEAT => 65,
		ItemIds::BEETROOT => 65,
		ItemIds::PUMPKIN => 65,
		ItemIds::CARVED_PUMPKIN => 65,
		ItemIds::RED_FLOWER => 65,
		ItemIds::YELLOW_FLOWER => 65,
		ItemIds::APPLE => 65,
		ItemIds::COOKIE => 85,
		ItemIds::BAKED_POTATO => 85,
		ItemIds::WHEAT_BLOCK => 85,
		ItemIds::BREAD => 85,
		ItemIds::CAKE => 100,
		ItemIds::PUMPKIN_PIE => 100
	];

	public function getFuelTime() : int{
		return 300;
	}

	protected function writeStateToMeta() : int{
		return $this->fill;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->fill = BlockDataSerializer::readBoundedInt("fill", $stateMeta, 0, 8);
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	private function isFilled(): bool{
		return $this->fill >= 8;
	}

	private function canPutRecycledFuel(): bool{
		if($this->fill >= 7){
			return false;
		}
		if(++$this->fill >= 7){
			$this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 20);
		}else{
			$this->position->getWorld()->setBlock($this->position, $this);
		}
		$this->position->getWorld()->addSound($this->position, new ComposterFillSuccessSound());
		return true;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player === null){
			return false;
		}
		if($this->isFilled()){
			$this->fill = 0;
			$this->position->getWorld()->setBlock($this->position, $this);
			$this->position->getWorld()->addSound($this->position, new ComposterReadySound());
			$this->position->getWorld()->dropItem($this->position->add(0.5, 1.1, 0.5), VanillaItems::BONE_MEAL());
		}elseif($this->fill < 7 && isset($this->ingredients[$item->getId()])){
			$item->pop();
			$player->getWorld()->broadcastPacketToViewers($this->position, SpawnParticleEffectPacket::create(
				dimensionId: DimensionIds::OVERWORLD,
				actorUniqueId: $player->getId(),
				position: $this->position->add(0.5, 0.5, 0.5),
				particleName: "minecraft:crop_growth_emitter",
				molangVariablesJson: "" //default none
			));
			if($this->fill > 0 && random_int(0, 100) <= $this->ingredients[$item->getId()]){
				$this->canPutRecycledFuel();
			}elseif($this->fill === 0){
				$this->canPutRecycledFuel();
			}
			$this->position->getWorld()->addSound($this->position, new ComposterFillSound());
		}
		return true;
	}

	public function onScheduledUpdate() : void{
		if($this->fill !== 7){
			return;
		}
		++$this->fill;
		$this->position->getWorld()->setBlock($this->position, $this);
		$this->position->getWorld()->addSound($this->position, new ComposterReadySound());
	}
}