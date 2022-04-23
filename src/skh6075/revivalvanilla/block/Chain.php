<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Block;
use pocketmine\block\utils\PillarRotationInMetadataTrait;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class Chain extends Block{
	use PillarRotationInMetadataTrait;

	protected function getAxisMetaShift() : int{
		return 0;
	}

	protected function writeStateToMeta() : int{
		return [Axis::Y => 0, Axis::X => 1, Axis::Z => 2][$this->axis] ?? 0;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->axis = [0 => Axis::Y, 1 => Axis::X, 2 => Axis::Z][$stateMeta] ?? Axis::Y;
	}

	public function getStateBitmask() : int{
		return 0b10;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim($this->axis << 1, 0.3)->trim(Facing::opposite($this->axis << 1), 0.3)];
	}
}