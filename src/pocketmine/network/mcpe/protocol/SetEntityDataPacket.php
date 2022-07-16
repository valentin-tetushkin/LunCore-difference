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

class SetEntityDataPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::SET_ENTITY_DATA_PACKET;

	public $eid;
	public $metadata;

	/**
	 *
	 */
	public function decode(){
        $this->eid = $this->getEntityId();
        $this->metadata = $this->getEntityMetadata();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid);
		$this->putEntityMetadata($this->metadata);
	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "SetEntityDataPacket";
	}

}
