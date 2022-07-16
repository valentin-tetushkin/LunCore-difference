<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

namespace raklib\protocol;

class AdvertiseSystem extends Packet{
	public static $ID = MessageIdentifiers::ID_ADVERTISE_SYSTEM;

	/** @var string */
	public $serverName;

	protected function encodePayload(){
		$this->putString($this->serverName);
	}

	protected function decodePayload(){
		$this->serverName = $this->getString();
	}
}