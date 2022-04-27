<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\world\sound\entity;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;
use skh6075\revivalvanilla\entity\passive\Chicken;

class LayEggSound implements Sound{

	public function __construct(private Chicken $chicken){}

	public function encode(Vector3 $pos) : array{
		return [LevelSoundEventPacket::create(LevelSoundEvent::LAY_EGG, $pos, -1, EntityIds::CHICKEN, $this->chicken->isBaby(), false)];
	}
}