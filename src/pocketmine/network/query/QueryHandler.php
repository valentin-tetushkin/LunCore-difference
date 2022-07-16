<?php

namespace pocketmine\network\query;

use pocketmine\network\AdvancedSourceInterface;
use pocketmine\Server;
use pocketmine\utils\Binary;

class QueryHandler {
	private $server, $lastToken, $token;

	const HANDSHAKE = 9;
	const STATISTICS = 0;

	/**
	 * QueryHandler constructor.
	 */
	public function __construct(){
		$this->server = Server::getInstance();
		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.server.query.start"));
		$addr = ($ip = $this->server->getIp()) != "" ? $ip : "0.0.0.0";
		$port = $this->server->getPort();
		$this->regenerateToken();
		$this->lastToken = $this->token;
		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.server.query.running", [$addr, $port]));
	}

	private function debug(string $message) : void{
		//TODO: replace this with a proper prefixed logger
		$this->server->getLogger()->debug("[Query] $message");
	}

	/**
	 * @deprecated
	 *
	 * @return void
	 */
	public function regenerateInfo(){

	}

	/**
	 * @return void
	 */
	public function regenerateToken(){
		$this->lastToken = $this->token;
		$this->token = random_bytes(16);
	}

	/**
	 * @param $token
	 * @param $salt
	 *
	 * @return int
	 */
	public static function getTokenString($token, $salt){
		return Binary::readInt(substr(hash("sha512", $salt . ":" . $token, true), 7, 4));
	}

	/**
	 * @return void
	 */
    public function handle(AdvancedSourceInterface $interface, string $address, int $port, string $packet){
		$offset = 2;
		$packetType = ord($packet[$offset++]);
		$sessionID = Binary::readInt(substr($packet, $offset, 4));
		$offset += 4;
		$payload = substr($packet, $offset);

		switch($packetType){
			case self::HANDSHAKE: //Handshake
				$reply = chr(self::HANDSHAKE);
				$reply .= Binary::writeInt($sessionID);
				$reply .= self::getTokenString($this->token, $address) . "\x00";

				$interface->sendRawPacket($address, $port, $reply);
				break;
			case self::STATISTICS: //Stat
				$token = Binary::readInt(substr($payload, 0, 4));
				if($token !== ($t1 = self::getTokenString($this->token, $address)) and $token !== ($t2 = self::getTokenString($this->lastToken, $address))){
					$this->debug("Bad token $token from $address $port, expected $t1 or $t2");
					break;
				}
				$reply = chr(self::STATISTICS);
				$reply .= Binary::writeInt($sessionID);

				if(strlen($payload) === 8){
					$reply .= $this->server->getQueryInformation()->getLongQuery();
				}else{
					$reply .= $this->server->getQueryInformation()->getShortQuery();
				}
				$interface->sendRawPacket($address, $port, $reply);
				break;
			default:
				$this->debug("Unhandled packet from $address $port: " . base64_encode($packet));
				break;
		}
	}

}
