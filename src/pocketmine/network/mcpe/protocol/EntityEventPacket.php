<?php

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class EntityEventPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::ENTITY_EVENT_PACKET;

	const JUMP = 1;
	const HURT_ANIMATION = 2;
	const DEATH_ANIMATION = 3;

	const TAME_FAIL = 6;
	const TAME_SUCCESS = 7;
	const SHAKE_WET = 8;
	const USE_ITEM = 9;
	const EAT_GRASS_ANIMATION = 10;
	const FISH_HOOK_BUBBLE = 11;
	const FISH_HOOK_POSITION = 12;
	const FISH_HOOK_HOOK = 13;
	const FISH_HOOK_TEASE = 14;
	const SQUID_INK_CLOUD = 15;
	const AMBIENT_SOUND = 16;
	const RESPAWN = 17;

	const EATING_ITEM = 57;

	const CONSUME_TOTEM = 65;

	//TODO add new events

	public $eid;
	public $event;
	public $data = 0;

	/**
	 *
	 */
	public function decode(){
		$this->eid = $this->getEntityId();
		$this->event = $this->getByte();
		$this->data = $this->getVarInt();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid);
		$this->putByte($this->event);
		$this->putVarInt($this->data);
	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "EntityEventPacket";
	}

}
