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


class ServerToClientHandshakePacket extends DataPacket {
	const NETWORK_ID = ProtocolInfo::SERVER_TO_CLIENT_HANDSHAKE_PACKET;

	public $publicKey;
	public $serverToken;

	/**
	 * @return bool
	 */
	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	/**
	 *
	 */
	public function decode(){
		$this->publicKey = $this->getString();
		$this->serverToken = $this->getString();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putString($this->publicKey);
		$this->putString($this->serverToken);
	}
}