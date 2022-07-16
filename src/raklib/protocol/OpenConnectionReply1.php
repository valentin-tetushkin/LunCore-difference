<?php

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

class OpenConnectionReply1 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_1;

	/** @var int */
	public $serverID;
	/** @var bool */
	public $serverSecurity = false;
	/** @var int */
	public $mtuSize;

	protected function encodePayload(){
		$this->writeMagic();
		$this->putLong($this->serverID);
		$this->putByte($this->serverSecurity ? 1 : 0);
		$this->putShort($this->mtuSize);

		//$this->put(str_repeat("\x00", $this->mtuSize - strlen($this->buffer) - 28)); //Если не заходят игроки с Украины и т.п
	}

	protected function decodePayload(){
		$this->readMagic();
		$this->serverID = $this->getLong();
		$this->serverSecurity = $this->getByte() !== 0;
		$this->mtuSize = $this->getShort();
	}
}