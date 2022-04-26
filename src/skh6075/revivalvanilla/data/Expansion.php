<?php

/**
 * @link https://github.com/presentkim-pm/ExpansionPack/blob/main/src/kim/present/expansionpack/Loader.php
 */

declare(strict_types=1);

namespace skh6075\revivalvanilla\data;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockToolType;
use pocketmine\block\tile\TileFactory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier as IID;
use pocketmine\item\ToolTier;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use skh6075\revivalvanilla\block\Campfire;
use skh6075\revivalvanilla\block\Chain;
use skh6075\revivalvanilla\block\Composter;
use skh6075\revivalvanilla\block\Scaffolding;
use skh6075\revivalvanilla\block\tile\campfire\RegularCampfireTile;
use skh6075\revivalvanilla\block\tile\campfire\SoulCampfireTile;
use skh6075\revivalvanilla\data\resource\BlockIds;
use skh6075\revivalvanilla\data\resource\ItemIds;
use skh6075\revivalvanilla\data\resource\TileIds;
use skh6075\revivalvanilla\item\Shield;
use skh6075\revivalvanilla\task\async\RuntimeIdsRegister;

final class Expansion{
	use SingletonTrait;

	public static function getInstance() : Expansion{
		return self::$instance ??= new self;
	}

	private function __construct(){
		$this->registerAllTiles();
		$this->registerAllItems();
		$this->registerAllBlocks();
		$this->registerAllRuntimeIds();
		$this->registerAllCreativeItems();
	}

	private function registerAllTiles(): void{
		/** @var TileFactory $tileFactory */
		$tileFactory = TileFactory::getInstance();
		$tileFactory->register(RegularCampfireTile::class, [TileIds::CAMPFIRE, TileIds::LEGACY_CAMPFIRE]);
		$tileFactory->register(SoulCampfireTile::class, [TileIds::SOUL_CAMPFIRE, TileIds::LEGACY_SOUL_CAMPFIRE]);
	}

	private function registerAllItems() : void{
		/** @var ItemFactory $factory */
		$factory = ItemFactory::getInstance();
		$factory->register(new Shield(new IID(ItemIds::SHIELD, 0), "Shield"), true);
	}

	private function registerAllBlocks() : void{
		$this->registerBlock(new Campfire(new BID(BlockIds::CAMPFIRE, 0, ItemIds::CAMPFIRE, RegularCampfireTile::class), "Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));
		$this->registerBlock(new Campfire(new BID(BlockIds::SOUL_CAMPFIRE, 0, ItemIds::SOUL_CAMPFIRE, SoulCampfireTile::class), "Soul Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));

		$this->registerBlock(new Chain(new BID(BlockIds::CHAIN, 0, ItemIds::CHAIN), "Chain", new BlockBreakInfo(5, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())));

		$this->registerBlock(new Composter(new BID(BlockIds::COMPOSTER, 0, ItemIds::COMPOSTER), "Composter", new BreakInfo(0.6, BlockToolType::AXE)));

		$this->registerBlock(new Scaffolding(new BID(BlockIds::SCAFFOLDING, 0, ItemIds::SCAFFOLDING), "Scaffolding", new BlockBreakInfo(0, BlockToolType::AXE | BlockToolType::SWORD)));
	}

	private function registerBlock(Block $block) : void{
		$idInfo = $block->getIdInfo();
		$itemId = $idInfo->getItemId();
		if(255 - $idInfo->getBlockId() !== $idInfo->getItemId()){
			ItemFactory::getInstance()->register(new ItemBlock(new IID($itemId, 0), $block), true);
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
}