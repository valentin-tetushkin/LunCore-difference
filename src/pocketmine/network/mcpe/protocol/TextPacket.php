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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class TextPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::TEXT_PACKET;

	const TYPE_RAW = 0;
	const TYPE_CHAT = 1;
	const TYPE_TRANSLATION = 2;
	const TYPE_POPUP = 3;
	const TYPE_TIP = 4;
	const TYPE_SYSTEM = 5;
	const TYPE_WHISPER = 6;

	public $type;
	public $source;
	public $message;
	public $parameters = [];

	/**
	 *
	 */
	public function decode(){
		$this->type = $this->getByte();
		switch($this->type){
			case self::TYPE_POPUP:
			case self::TYPE_CHAT:
				/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_WHISPER:
				$this->source = $this->getString();
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
				$this->message = $this->getString();
				break;

			case self::TYPE_TRANSLATION:
				$this->message = $this->getString();
				$count = $this->getUnsignedVarInt();
				for($i = 0; $i < $count && !$this->feof(); ++$i){
					$this->parameters[] = $this->getString();
				}
		}
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putByte($this->type);
		switch($this->type){
			case self::TYPE_POPUP:
			case self::TYPE_CHAT:
				/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_WHISPER:
				$this->putString($this->source);
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
				$this->putString($this->message);
				break;

			case self::TYPE_TRANSLATION:
				$this->putString($this->message);
				$this->putUnsignedVarInt(count($this->parameters));
				foreach($this->parameters as $p){
					$this->putString($p);
				}
		}
	}

	/**
	 * @return string
	 */
	public function getName(){
		return "TextPacket";
	}

}
