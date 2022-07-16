<?php

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class AddPaintingPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::ADD_PAINTING_PACKET;

	public $eid;
	public $x;
	public $y;
	public $z;
	public $direction;
	public $title;

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
		$this->putEntityId($this->eid); //EntityUniqueID
		$this->putEntityId($this->eid); //EntityRuntimeID
		$this->putBlockCoords($this->x, $this->y, $this->z);
		$this->putVarInt($this->direction);
		$this->putString($this->title);
	}

	/**
	 * @return string
	 */
	public function getName(){
		return "AddPaintingPacket";
	}

}
