<?php

/**
 * @link https://github.com/presentkim-pm/ExpansionPack/blob/main/src/kim/present/expansionpack/task/RuntimeIdsRegister.php
 */

declare(strict_types=1);

namespace skh6075\revivalvanilla\task\async;

use Exception;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\scheduler\AsyncTask;

final class RuntimeIdsRegister extends AsyncTask{

	public function onRun() : void{
		self::register();
	}

	/** Register all block runtime ids from canonical_block_states.nbt */
	public static function register() : void{
		(function(){ //HACK : Closure bind hack to access inaccessible members
			$stringToLegacyMap = LegacyBlockIdToStringIdMap::getInstance()->getStringToLegacyMap();
			$metaMap = [];
			/** @see RuntimeBlockMapping::getBedrockKnownStates() */
			foreach($this->getBedrockKnownStates() as $runtimeId => $state){
				try{
					$name = $state->getString("name");
					if(!isset($stringToLegacyMap[$name])){
						continue;
					}

					$legacyId = $stringToLegacyMap[$name];
					if(!isset($metaMap[$legacyId])){
						$metaMap[$legacyId] = 0;
					}

					$meta = $metaMap[$legacyId]++;
					if($meta > 0xf){
						continue;
					}

					/** @see RuntimeBlockMapping::$runtimeToLegacyMap */
					if(isset($this->runtimeToLegacyMap[$runtimeId])){
						continue;
					}

					$this->registerMapping($runtimeId, $legacyId, $meta);
				}catch(Exception){
				}
			}
		})->call(RuntimeBlockMapping::getInstance());
	}
}