<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla\data\mapping;

use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\entity\passive\Chicken;

final class EntityDataMapping{
	use SingletonTrait;

	public static function getInstance() : EntityDataMapping{
		return self::$instance ??= new self;
	}

	private LegacyEntityIdToStringIdMap $entityIdToStringIdMap;

	/**
	 * @phpstan-var array<int, string>
	 * @var string[]
	 */
	private array $legacyToClass = [];

	private function __construct(){
		$this->entityIdToStringIdMap = LegacyEntityIdToStringIdMap::getInstance();
		$this->register(Chicken::class);
	}

	/** @noinspection PhpUndefinedMethodInspection */
	private function register(string $class): void{
		$this->legacyToClass[$this->entityIdToStringIdMap->stringToLegacy($class::getNetworkTypeId())] = $class;
	}

	public function getAll(): array{
		return $this->legacyToClass;
	}
}