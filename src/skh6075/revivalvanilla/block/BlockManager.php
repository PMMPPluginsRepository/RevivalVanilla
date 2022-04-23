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
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ToolTier;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\data\BlockIds;
use skh6075\revivalvanilla\data\ItemIds;
use skh6075\revivalvanilla\task\async\RuntimeIdsRegister;

final class BlockManager{
	use SingletonTrait;

	public static function getInstance() : BlockManager{
		return self::$instance ??= new self;
	}

	private function __construct(){
		$this->registerAllBlocks();
		$this->registerAllRuntimeIds();
		$this->registerAllCreativeItems();
	}

	private function registerAllBlocks() : void{
		$this->registerBlock(new Campfire(new BID(BlockIds::CAMPFIRE, 0, ItemIds::CAMPFIRE), "Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));
		$this->registerBlock(new Campfire(new BID(BlockIds::SOUL_CAMPFIRE, 0, ItemIds::SOUL_CAMPFIRE), "Soul Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));

		$this->registerBlock(new Chain(new BID(BlockIds::CHAIN, 0, ItemIds::CHAIN), "Chain", new BlockBreakInfo(5, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())));
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
		$idInfo = $block->getIdInfo();
		$itemId = $idInfo->getItemId();
		if(255 - $idInfo->getBlockId() !== $idInfo->getItemId()){
			ItemFactory::getInstance()->register(new ItemBlock(new ItemIdentifier($itemId, 0), $block), true);
		}
		BlockFactory::getInstance()->register($block, true);
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