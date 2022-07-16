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

namespace pocketmine\network\mcpe;

use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\network\Network;
use pocketmine\network\AdvancedSourceInterface;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\PacketReliability;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use raklib\utils\InternetAddress;

class RakLibInterface implements ServerInstance, AdvancedSourceInterface{
    /**
     * Иногда это меняется, когда протокол уровня MCPE ломается до такой степени, что старый и новый не могут
     * общаться. Важно, чтобы мы проверили это, чтобы избежать катастроф.
     */
	private const MCPE_RAKNET_PROTOCOL_VERSION = 8;

	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

	/** @var RakLibServer */
	private $rakLib;

	/** @var Player[] */
	private $players = [];

	/** @var string[] */
	private $identifiers = [];

	/** @var int[] */
	private $identifiersACK = [];

	/** @var ServerHandler */
	private $interface;

	/** @var SleeperNotifier */
	private $sleeper;

	/** @var RaklibPacketLogger */
	private $packetLogger = null;

	public function __construct(Server $server){
		$this->server = $server;

		$this->sleeper = new SleeperNotifier();

		$isEnable = (bool) $this->server->getProperty("debug.logs", false);
		if ($isEnable !== false) {
			$this->packetLogger = new RaklibPacketLogger(
			    $this->server->getDataPath()."logs".DIRECTORY_SEPARATOR
		    );
		    $this->packetLogger->start(PTHREADS_INHERIT_NONE);
		}else{
			$logger = $this->server->getLogger();
			$logger->notice("(LunCore) - Логи пакетов выключены.");
		}

		$this->rakLib = new RakLibServer(
			$this->server->getLogger(),
			$this->server->getLoader(),
			new InternetAddress($this->server->getIp(), $this->server->getPort(), 4),
			(int) $this->server->getProperty("network.max-mtu-size", 1492),
			self::MCPE_RAKNET_PROTOCOL_VERSION,
			$this->sleeper
		);
		$this->interface = new ServerHandler($this->rakLib, $this);
	}

	public function start(){
		$this->server->getTickSleeper()->addNotifier($this->sleeper, function() : void{
			$this->process();
		});
		$this->rakLib->start(PTHREADS_INHERIT_CONSTANTS); //HACK: MainLogger needs constants for exception logging
	}

	public function setNetwork(Network $network){
		$this->network = $network;
	}

    /**
     * @throws \Exception
     */
    public function process() : void{
		while($this->interface->handlePacket()){}

		if(!$this->rakLib->isRunning() and !$this->rakLib->isShutdown()){
			throw new \Exception("RakLib Thread crashed");
		}
	}

	public function closeSession(string $identifier, string $reason) : void{
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			unset($this->identifiers[spl_object_hash($player)]);
			unset($this->players[$identifier]);
			unset($this->identifiersACK[$identifier]);
			$player->close($player->getLeaveMessage(), $reason);
		}
	}

	public function close(Player $player, $reason = "unknown reason"){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			unset($this->players[$this->identifiers[$h]]);
			unset($this->identifiersACK[$this->identifiers[$h]]);
			$this->interface->closeSession($this->identifiers[$h], $reason);
			unset($this->identifiers[$h]);
		}
	}

	public function shutdown(){
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->shutdown();

		if($this->packetLogger !== null){
			$this->packetLogger->shutdown();
		}
	}

	public function emergencyShutdown(){
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->emergencyShutdown();

		if($this->packetLogger !== null){
			$this->packetLogger->shutdown();
		}
	}

	public function openSession(string $identifier, string $address, int $port, int $clientID) : void{
		$ev = new PlayerCreationEvent($this, Player::class, Player::class, $address, $port);
		$this->server->getPluginManager()->callEvent($ev);
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getAddress(), $ev->getPort());
		$this->players[$identifier] = $player;
		$this->identifiersACK[$identifier] = 0;
		$this->identifiers[spl_object_hash($player)] = $identifier;
		$this->server->addPlayer($player);
	}

	public function handleEncapsulated(string $identifier, EncapsulatedPacket $packet, int $flags) : void{
		if(isset($this->players[$identifier])){
			//get this now for blocking in case the player was closed before the exception was raised
			$player = $this->players[$identifier];
			$address = $player->getAddress();
			try{
				if($packet->buffer !== ""){
					$pk = $this->getPacket($packet->buffer);

					if($this->packetLogger !== null){
						$this->packetLogger->putPacket($address, $packet->buffer);
					}

					$pk->decode();
					$player->handleDataPacket($pk);
				}
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				$logger->debug("Packet " . (isset($pk) ? get_class($pk) : "unknown") . ": " . base64_encode($packet->buffer));
				$logger->logException($e);

				//$player->close($player->getLeaveMessage(), "Internal server error");
				//$this->interface->blockAddress($address, 5);
			}
		}
	}

	public function blockAddress($address, $timeout = 300){
		$this->interface->blockAddress($address, $timeout);
	}

	public function unblockAddress($address){
		$this->interface->unblockAddress($address);
	}

	public function handleRaw(string $address, int $port, string $payload) : void{
		$this->server->handlePacket($this, $address, $port, $payload);
	}

	public function sendRawPacket($address, $port, $payload){
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function notifyACK(string $identifier, int $identifierACK) : void{

	}

	public function setName(string $name){
		$info = $this->server->getQueryInformation();

		$this->interface->sendOption("name", implode(";",
			[
				"MCPE",
				rtrim(addcslashes($name, ";"), '\\'),
				ProtocolInfo::CURRENT_PROTOCOL,
				ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				$info->getPlayerCount(),
				$info->getMaxPlayerCount(),
				$this->rakLib->getServerId(),
				$this->server->getName(),
				Server::getGamemodeName($this->server->getGamemode())
			]) . ";"
		);
	}

	/**
	 * @param bool $name
	 *
	 * @return void
	 */
	public function setPortCheck($name){
		$this->interface->sendOption("portChecking", (bool) $name);
	}

	public function setPacketLimit(int $limit) : void{
		$this->interface->sendOption("packetLimit", $limit);
	}

	public function handleOption(string $option, string $value) : void{
		if($option === "bandwidth"){
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			$identifier = $this->identifiers[$h];
			if(!$packet->isEncoded){
				$packet->encode();
				$packet->isEncoded = true;
			}

			if($packet instanceof BatchPacket){
				if($needACK){
					$pk = new EncapsulatedPacket();
					$pk->identifierACK = $this->identifiersACK[$identifier]++;
					$pk->buffer = $packet->buffer;
					$pk->reliability = PacketReliability::RELIABLE_ORDERED;
					$pk->orderChannel = 0;
				}else{
					if(!isset($packet->__encapsulatedPacket)){
						$packet->__encapsulatedPacket = new CachedEncapsulatedPacket;
						$packet->__encapsulatedPacket->identifierACK = null;
						$packet->__encapsulatedPacket->buffer = $packet->buffer;
						$packet->__encapsulatedPacket->reliability = PacketReliability::RELIABLE_ORDERED;
						$packet->__encapsulatedPacket->orderChannel = 0;
					}
					$pk = $packet->__encapsulatedPacket;
				}

				$this->interface->sendEncapsulated($identifier, $pk, ($needACK ? RakLib::FLAG_NEED_ACK : 0) | ($immediate ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
				return $pk->identifierACK;
			}else{
				$this->server->batchPackets([$player], [$packet], true, $immediate);
				return null;
			}
		}

		return null;
	}

	private function getPacket($buffer){
		$pid = ord($buffer[0]);
		if(($data = $this->network->getPacket($pid)) === null){
			return null;
		}
		$data->setBuffer($buffer, 1);
		
		return $data;
	}

	public function updatePing(string $identifier, int $pingMS) : void{
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$player->setPing($pingMS);
		}
	}
}