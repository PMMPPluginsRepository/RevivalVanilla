<?php

/**
 * @link https://github.com/presentkim-pm/ExpansionPack/blob/main/src/kim/present/expansionpack/Loader.php
 */

declare(strict_types=1);

namespace skh6075\revivalvanilla\block;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\task\async\RuntimeIdsRegister;

final class BlockManager{
	use SingletonTrait;

	public static function getInstance() : BlockManager{
		return self::$instance ??= new self;
	}

	private function __construct(){
		$this->registerAllRuntimeIds();
		$this->registerAllBlocks();
		$this->registerAllCreativeItems();
	}

	private function registerAllBlocks(): void{
		$this->registerBlock(new Campfire(new BID(BlockLegacyIds::CAMPFIRE, 0, ItemIds::CAMPFIRE), "Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));
		$this->registerBlock(new Campfire(new BID(545, 0, -290), "Soul Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));
	}

	private function registerAllRuntimeIds() : void{
		RuntimeIdsRegister::register();
		$asyncPool = Server::getInstance()->getAsyncPool();
		foreach($asyncPool->getRunningWorkers() as $workerId){
			$asyncPool->submitTaskToWorker(new RuntimeIdsRegister(), $workerId);
		}
		$asyncPool->addWorkerStartHook(function(int $workerId) use ($asyncPool) : void{
			$asyncPool->submitTaskToWorker(new RuntimeIdsRegister(), $workerId);
		});
	}

	private function registerBlock(Block $block) : void{
		BlockFactory::getInstance()->register($block, true);
		ItemFactory::getInstance()->register($block->asItem(), true);
		CreativeInventory::getInstance()->add($block->asItem());
	}

	private function registerAllCreativeItems() : void{
		$originItems = CreativeInventory::getInstance()->getAll();

		CreativeInventory::reset();
		$inv = CreativeInventory::getInstance();
		foreach($originItems as $item){
			if(!$inv->contains($item)){
				$inv->add($item);
			}
		}
	}
}