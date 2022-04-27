<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\entity\ai\path;

use skh6075\revivalvanilla\entity\ai\navigator\EntityNavigator;
use pocketmine\world\Position;

abstract class PathFinder{

	public function __construct(protected EntityNavigator $navigator){}

	/**
	 * 기존에 탐색했던 데이터를 제거합니다
	 */
	abstract public function reset() : void;

	/**
	 * 최적 경로를 탐색해 결과를 도출합니다
	 *
	 * @return Position[]|null
	 */
	abstract public function search() : ?array;

}