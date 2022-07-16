<?php


/*
 * 
 * 
 * @author LunCore team
 * @link http://vk.com/luncore
 * 
 *
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * 
 * 
 * @author LunCore team
 * @link http://vk.com/luncore
 * 
 *
*/

namespace pocketmine\metadata;

use pocketmine\level\Level;

class LevelMetadataStore extends MetadataStore {

	/**
	 * @param Metadatable $level
	 * @param string      $metadataKey
	 *
	 * @return string
	 */
	public function disambiguate(Metadatable $level, $metadataKey){
		if(!($level instanceof Level)){
            throw new \InvalidArgumentException("Аргумент должен быть экземпляром уровня");
		}

		return strtolower($level->getName()) . ":" . $metadataKey;
	}
}