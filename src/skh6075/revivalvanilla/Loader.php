<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\data\Expansion;

final class Loader extends PluginBase{
	use SingletonTrait;

	public static function getInstance() : Loader{
		return self::$instance;
	}

	private Expansion $expansion;

	protected function onLoad() : void{
		self::$instance = $this;
		$this->expansion = Expansion::getInstance();
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}
}