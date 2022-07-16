<?php

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

class ItemFrameDropItemPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET;

	public $x;
	public $y;
	public $z;

	//public $item;

	/**
	 *
	 */
	public function decode(){
		$this->getBlockCoords($this->x, $this->y, $this->z);
		//$this->item = $this->getSlot();
	}

	/**
	 *
	 */
	public function encode(){

	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "ItemFrameDropItemPacket";
	}

}