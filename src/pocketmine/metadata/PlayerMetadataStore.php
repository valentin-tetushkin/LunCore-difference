<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * @author LunCore team
 * @link http://vk.com/luncore
 * @creator vk.com/klainyt
 *
*/

namespace pocketmine\metadata;

use pocketmine\IPlayer;

class PlayerMetadataStore extends MetadataStore {

	/**
	 * @param Metadatable $player
	 * @param string      $metadataKey
	 *
	 * @return string
	 */
	public function disambiguate(Metadatable $player, $metadataKey){
		if(!($player instanceof IPlayer)){
            throw new \InvalidArgumentException("Аргумент должен быть экземпляром IPlayer");
		}

		return strtolower($player->getName()) . ":" . $metadataKey;
	}
}
