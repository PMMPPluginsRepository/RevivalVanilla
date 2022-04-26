<?php

declare(strict_types=1);

namespace skh6075\revivalvanilla;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use skh6075\revivalvanilla\item\Shield;

class EventListener implements Listener{

	public function onPlayerToggleSneakEvent(PlayerToggleSneakEvent $event): void{
		$player = $event->getPlayer();
		if($player->getInventory()->getItemInHand() instanceof Shield || $player->getOffHandInventory()->getItem(0) instanceof Shield){
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::BLOCKED_USING_SHIELD, $event->isSneaking());
			$player->sendData(null);
		}
	}
}