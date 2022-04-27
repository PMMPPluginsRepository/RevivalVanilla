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
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Armor;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\Axe;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier as IID;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\SpawnEgg;
use pocketmine\item\Sword;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use PrefixedLogger;
use skh6075\revivalvanilla\block\Campfire;
use skh6075\revivalvanilla\block\Chain;
use skh6075\revivalvanilla\block\Composter;
use skh6075\revivalvanilla\block\Scaffolding;
use skh6075\revivalvanilla\block\tile\campfire\RegularCampfireTile;
use skh6075\revivalvanilla\block\tile\campfire\SoulCampfireTile;
use skh6075\revivalvanilla\data\mapping\EntityDataMapping;
use skh6075\revivalvanilla\data\resource\BlockIds;
use skh6075\revivalvanilla\data\resource\ItemIds;
use skh6075\revivalvanilla\data\resource\TileIds;
use skh6075\revivalvanilla\entity\LivingBase;
use skh6075\revivalvanilla\item\Shield;
use skh6075\revivalvanilla\task\async\RuntimeIdsRegister;

final class Expansion{
	use SingletonTrait;

	public static function getInstance() : Expansion{
		return self::$instance ??= new self;
	}

	private PrefixedLogger $logger;

	private ItemFactory $itemFactory;

	private BlockFactory $blockFactory;

	/** @noinspection PhpUndefinedMethodInspection */
	private function __construct(){
		$this->logger = new PrefixedLogger(Server::getInstance()->getLogger(), "Expansion");
		$this->itemFactory = ItemFactory::getInstance();
		$this->blockFactory = BlockFactory::getInstance();

		$entityFactory = EntityFactory::getInstance();
		foreach(EntityDataMapping::getInstance()->getAll() as $legacyId => $class){
			$name = explode(DIRECTORY_SEPARATOR, $class)[count(explode(DIRECTORY_SEPARATOR, $class)) - 1]; //bad variable
			$this->logger->debug("$name entity is registered [LegacyId: $legacyId]");
			$entityFactory->register($class, function(World $world, CompoundTag $nbt) use ($class): LivingBase{
				return new $class(EntityDataHelper::parseLocation($nbt, $world), $nbt);
			}, [$class::getNetworkTypeId()]);

			$this->itemFactory->register(new class(new IID(ItemIds::SPAWN_EGG, $legacyId), "Spawn Egg", $class) extends SpawnEgg{
				public function __construct(IID $identifier, string $name, private string $entity){
					parent::__construct($identifier, $name);
				}

				protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
					return new $this->entity(Location::fromObject($pos, $world, $yaw, $pitch));
				}
			}, true);
		}
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

	/** @noinspection PhpUndefinedMethodInspection */
	private function registerAllItems() : void{
		ExpansionPack::netheriteToolTier();

		(function(): void{
			$this->register(new Shield(new IID(ItemIds::SHIELD, 0), "Shield"), true);

			$this->register(new Item(new IID(ItemIds::NETHERITE_INGOT, 0), "Netherite Ingot"), true);
			$this->register(new Item(new IID(ItemIds::NETHERITE_SCRAP, 0), "Netherite Scrap"), true);
			$this->register(new Sword(new IID(ItemIds::NETHERITE_SWORD, 0), "Netherite Sword", ToolTier::NETHERITE()), true);
			$this->register(new Pickaxe(new IID(ItemIds::NETHERITE_PICKAXE, 0), "Netherite Pickaxe", ToolTier::NETHERITE()), true);
			$this->register(new Shovel(new IID(ItemIds::NETHERITE_SHOVEL, 0), "Netherite Shovel", ToolTier::NETHERITE()), true);
			$this->register(new Axe(new IID(ItemIds::NETHERITE_AXE, 0), "Netherite Axe", ToolTier::NETHERITE()), true);
			$this->register(new Hoe(new IID(ItemIds::NETHERITE_HOE, 0), "Netherite Hoe", ToolTier::NETHERITE()), true);
			$this->register(new Armor(new IID(ItemIds::NETHERITE_HELMET, 0), "Netherite Helmet", new ArmorTypeInfo(3, 407, ArmorInventory::SLOT_HEAD)));
			$this->register(new Armor(new IID(ItemIds::NETHERITE_CHESTPLATE, 0), "Netherite Chestplate", new ArmorTypeInfo(8, 592, ArmorInventory::SLOT_CHEST)));
			$this->register(new Armor(new IID(ItemIds::NETHERITE_LEGGINGS, 0), "Netherite Leggings", new ArmorTypeInfo(6, 555, ArmorInventory::SLOT_LEGS)));
			$this->register(new Armor(new IID(ItemIds::NETHERITE_BOOTS, 0), "Netherite Boots", new ArmorTypeInfo(3, 481, ArmorInventory::SLOT_FEET)));
		})->call($this->itemFactory);
	}

	private function registerAllBlocks() : void{
		$this->registerBlock(new Campfire(new BID(BlockIds::CAMPFIRE, 0, ItemIds::CAMPFIRE, RegularCampfireTile::class), "Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));
		$this->registerBlock(new Campfire(new BID(BlockIds::SOUL_CAMPFIRE, 0, ItemIds::SOUL_CAMPFIRE, SoulCampfireTile::class), "Soul Campfire", new BlockBreakInfo(2, BlockToolType::AXE)));

		$this->registerBlock(new Chain(new BID(BlockIds::CHAIN, 0, ItemIds::CHAIN), "Chain", new BlockBreakInfo(5, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel())));

		$this->registerBlock(new Composter(new BID(BlockIds::COMPOSTER, 0, ItemIds::COMPOSTER), "Composter", new BreakInfo(0.6, BlockToolType::AXE)));

		$this->registerBlock(new Scaffolding(new BID(BlockIds::SCAFFOLDING, 0, ItemIds::SCAFFOLDING), "Scaffolding", new BlockBreakInfo(0, BlockToolType::AXE | BlockToolType::SWORD)));

		$this->registerBlock(new Block(new BID(BlockIds::NETHERITE_BLOCK, 0, ItemIds::NETHERITE_BLOCK), "Netherite", new BlockBreakInfo(50, BlockToolType::PICKAXE)));
		$this->registerBlock(new Block(new BID(BlockIds::ANCIENT_DEBRIS, 0, ItemIds::ANCIENT_DEBRIS), "Ancient Debris", new BlockBreakInfo(30, BlockToolType::PICKAXE)));
	}

	private function registerBlock(Block $block) : void{
		$idInfo = $block->getIdInfo();
		$itemId = $idInfo->getItemId();
		if(255 - $idInfo->getBlockId() !== $idInfo->getItemId()){
			$this->itemFactory->register(new ItemBlock(new IID($itemId, 0), $block), true);
		}
		$this->blockFactory->register($block, true);
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