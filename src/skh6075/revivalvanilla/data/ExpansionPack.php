<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\data;

use pocketmine\item\ToolTier;
use ReflectionClass;

final class ExpansionPack{

	public static function netheriteToolTier(): void{
		static $properties = [
			'enumName' => 'netherite',
			'maxDurability' => 2031,
			'baseAttackPoints' => 8,
			'baseEfficiency' => 9
		];
		$ref = new ReflectionClass(ToolTier::class);
		$method = $ref->getMethod("register");
		$method->setAccessible(true);

		/** @var ToolTier $toolTierClass */
		$toolTierClass = $ref->newInstanceWithoutConstructor();
		foreach($properties as $property => $value){
			$prop = $ref->getProperty($property);
			$prop->setAccessible(true);
			$prop->setValue($toolTierClass, $value);
		}
		$method->invoke(null, $toolTierClass);
	}
}