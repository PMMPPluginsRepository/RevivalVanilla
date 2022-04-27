<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Lava;
use pocketmine\block\Trapdoor;
use pocketmine\block\WoodenDoor;
use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use RuntimeException;

class EntityAI{

	public const WALL = 0;
	public const PASS = 1;
	public const BLOCK = 2;
	public const SLAB = 3;
	public const UP_SLAB = 4;
	public const DOOR = 5;

	public static function getHash(Vector3 $pos) : string{
		$pos = self::getFloorPos($pos);
		return "{$pos->x}:{$pos->y}:{$pos->z}";
	}

	public static function getFloorPos(Vector3 $pos) : Position{
		$newPos = new Position(Math::floorFloat($pos->x), $pos->getFloorY(), Math::floorFloat($pos->z), null);
		if($pos instanceof Position){
			$newPos->world = $pos->world;
		}
		return $newPos;
	}

	/**
	 * 특정 블럭이 어떤 상태인지를 확인해주는 메서드
	 *
	 * @param Block|Position $data
	 *
	 * @return int
	 */
	public static function checkBlockState(Block|Position $data) : int{
		if($data instanceof Position){
			$floor = self::getFloorPos($data);
			$block = $data->world->getBlockAt($floor->x, $floor->y, $floor->z);
		}elseif($data instanceof Block){
			$block = $data;
		}else{
			throw new RuntimeException("$data is not Block|Position class");
		}

		$value = self::BLOCK;
		if($block instanceof Door && count($block->getAffectedBlocks()) > 1){ //문일때
			$value = $block instanceof WoodenDoor ? self::DOOR : self::WALL; //철문인지 판단
		}else{
			$min = 256;
			$max = -1;
			foreach($block->getCollisionBoxes() as $_ => $bb){
				$min = min($min, $bb->minY);
				$max = max($max, $bb->maxY);
			}
			$blockBox = $block->getCollisionBoxes()[0] ?? null;
			$boxDiff = $blockBox === null ? 0 : $max - $min;
			if($boxDiff <= 0){
				if($block instanceof Lava){ //통과 가능 블럭중 예외처리
					$value = self::WALL;
				}else{
					$value = self::PASS;
				}
			}elseif($boxDiff > 1){ //울타리라면
				$value = self::WALL;
			}elseif($boxDiff <= 0.5){ //반블럭/카펫/트랩도어 등등
				$value = $blockBox->minY == (int) $blockBox->minY ? self::SLAB : self::UP_SLAB;
			}
		}
		return $block instanceof Trapdoor ? self::PASS : $value; //TODO: 트랩도어, 카펫 등
	}

	/**
	 * 블럭이 통과 가능한 위치인지를 판단하는 메서드
	 *
	 * @param Position   $pos
	 * @param Block|null $block
	 *
	 * @return int
	 */
	public static function checkPassablity(Position $pos, ?Block $block = null) : int{
		if($block === null){
			$floor = self::getFloorPos($pos);
			$block = $pos->world->getBlockAt($floor->x, $floor->y, $floor->z);
		}else{
			$floor = $block->getPosition();
		}
		$state = self::checkBlockState($block); //현재 위치에서의 블럭 상태가
		switch($state){
			case self::WALL:
			case self::DOOR: //벽이거나 문이라면 체크가 더이상 필요 없음
				return $state;
			case self::PASS: //통과가능시에
				//윗블럭도 통과 가능하다면 통과판정 아니라면 벽 판정
				return self::checkBlockState($floor->getSide(Facing::UP)) === self::PASS ? self::PASS : self::WALL;
			case self::BLOCK:
			case self::UP_SLAB: //블럭이거나 위에 설치된 반블럭일경우
				$up = self::checkBlockState($upBlock = $block->getSide(Facing::UP)); //y+1의 블럭이
				if($up === self::SLAB){ //반블럭 이고
					$up2 = self::checkBlockState($floor->getSide(Facing::UP, 2));
					//그 위가 통과 가능하며 블럭의 최고점과 자신의 위치의 차가 블럭 이하라면 블럭 판정
					return $up2 === self::PASS && $upBlock->getCollisionBoxes()[0]->maxY - $pos->y <= 1 ? self::BLOCK : self::WALL;
				}
				if($up === self::PASS){ //통과가능시에
					//y+ 2도 통과 가능이라면
					return self::checkBlockState($floor->getSide(Facing::UP, 2)) === self::PASS ?
						//블럭의 최고점과 자신의 위치의 차가 반블럭 이하라면 반블럭 판정 아니라면 블럭 판정
						($block->getCollisionBoxes()[0]->maxY - $pos->y <= 0.5 ? self::SLAB : self::BLOCK) : self::WALL;
				}
				return self::WALL;
			case self::SLAB:
				return (
					self::checkBlockState($floor->getSide(Facing::UP)) === self::PASS //y + 1이 통과가능하고
					&& (($up = self::checkBlockState($floor->getSide(Facing::UP, 2))) === self::PASS || $up === self::UP_SLAB) //y + 2을 통과가능(반블럭 포함)하면
				) ? self::SLAB : self::WALL;
		}
		return self::WALL;
	}

	/**
	 * 현재 위치에서 도달하게 될 최종 Y좌표를 계산합니다
	 *
	 * @param Position $pos
	 *
	 * @return float
	 */
	public static function calculateYOffset(Position $pos) : float{
		$newY = (int) $pos->y;
		switch(self::checkBlockState($pos)){
			case self::BLOCK:
				++$newY;
				break;
			case self::SLAB:
				$newY += 0.5;
				break;
			case self::PASS:
				$newPos = self::getFloorPos($pos);
				--$newPos->y;
				for(; $newPos->y >= 0; --$newPos->y){
					$block = $pos->world->getBlockAt($newPos->x, $newPos->y, $newPos->z);
					$state = self::checkBlockState($block);
					if($state === self::UP_SLAB || $state === self::BLOCK || $state === self::SLAB){
						foreach($block->getCollisionBoxes() as $_ => $bb){
							if($newPos->y < $bb->maxY){
								$newPos->y = $bb->maxY;
							}
						}
						break;
					}
				}
				$newY = $newPos->y;
				break;
		}
		return $newY;
	}

}