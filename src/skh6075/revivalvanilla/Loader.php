<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\block\BlockManager;

final class Loader extends PluginBase{
	use SingletonTrait;

	public static function getInstance() : Loader{
		return self::$instance;
	}

	private BlockManager $blockManager;

	protected function onLoad() : void{
		self::$instance = $this;
		$this->blockManager = BlockManager::getInstance();
	}
}