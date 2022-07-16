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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\entity\Attribute;


class UpdateAttributesPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

	public $entityId;

	/** @var Attribute[] */
	public $entries = [];

	/**
	 *
	 */
	public function decode(){

	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->entityId);
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putLFloat($entry->getMinValue());
			$this->putLFloat($entry->getMaxValue());
			$this->putLFloat($entry->getValue());
			$this->putLFloat($entry->getDefaultValue());
			$this->putString($entry->getName());
		}
	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "UpdateAttributesPacket";
	}

}