<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\entity\ai\navigator;

use JetBrains\PhpStorm\Pure;
use skh6075\revivalvanilla\entity\ai\path\PathFinder;
use skh6075\revivalvanilla\entity\ai\path\SimplePathFinder;
use pocketmine\math\Math;
use pocketmine\world\Position;

class WalkEntityNavigator extends EntityNavigator{

	public function canGoNextPath(Position $next) : bool{
		$pos = $this->holder->getPosition();
		return abs($pos->x - $next->x) < 0.1 && abs($pos->z - $next->z) < 0.1;
	}

	public function makeRandomGoal() : Position{
		$x = random_int(10, 30);
		$z = random_int(10, 30);
		$pos = $this->holder->getPosition();
		$pos->x = Math::floorFloat($pos->x) + 0.5 + (random_int(0, 1) ? $x : -$x);
		$pos->z = Math::floorFloat($pos->z) + 0.5 + (random_int(0, 1) ? $z : -$z);
		return $pos;
	}

	#[Pure]
	public function getDefaultPathFinder() : PathFinder{
		return new SimplePathFinder($this);
	}

}