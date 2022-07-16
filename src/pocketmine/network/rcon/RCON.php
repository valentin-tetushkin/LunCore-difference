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

declare(strict_types=1);

/**
 * Внедрение исходного протокола RCON для разрешения удаленных консольных команд.
 * Источник: https://developer.valvesoftware.com/wiki/Source_RCON_Protocol
 */
namespace pocketmine\network\rcon;

use pocketmine\command\RemoteConsoleCommandSender;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\TextFormat;
use function max;
use function socket_bind;
use function socket_close;
use function socket_create;
use function socket_create_pair;
use function socket_getsockname;
use function socket_last_error;
use function socket_listen;
use function socket_set_block;
use function socket_set_option;
use function socket_strerror;
use function socket_write;
use function trim;
use const AF_INET;
use const AF_UNIX;
use const SO_REUSEADDR;
use const SOCK_STREAM;
use const SOCKET_ENOPROTOOPT;
use const SOCKET_EPROTONOSUPPORT;
use const SOL_SOCKET;
use const SOL_TCP;

class RCON{
	/** @var Server */
	private $server;
	/** @var resource */
	private $socket;

	/** @var RCONInstance */
	private $instance;

	/** @var resource */
	private $ipcMainSocket;
	/** @var resource */
	private $ipcThreadSocket;

	public function __construct(Server $server, string $password, int $port = 19132, string $interface = "0.0.0.0", int $maxClients = 50){
		$this->server = $server;
		$this->server->getLogger()->info("Запуск прослушивателя удаленного управления");
		if($password === ""){
			throw new \InvalidArgumentException("Пустой пароль");
		}

		$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($socket === false){
			throw new \RuntimeException("Не удалось создать сокет:" . trim(socket_strerror(socket_last_error())));
		}
		$this->socket = $socket;

		if(!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)){
			throw new \RuntimeException("Не удалось установить параметр на сокете: " . trim(socket_strerror(socket_last_error())));
		}

		if(!@socket_bind($this->socket, $interface, $port) or !@socket_listen($this->socket, 5)){
			throw new \RuntimeException(trim(socket_strerror(socket_last_error())));
		}

		socket_set_block($this->socket);

		$ret = @socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $ipc);
		if(!$ret){
			$err = socket_last_error();
			if(($err !== SOCKET_EPROTONOSUPPORT and $err !== SOCKET_ENOPROTOOPT) or !@socket_create_pair(AF_INET, SOCK_STREAM, 0, $ipc)){
				throw new \RuntimeException(trim(socket_strerror(socket_last_error())));
			}
		}

		[$this->ipcMainSocket, $this->ipcThreadSocket] = $ipc;

		$notifier = new SleeperNotifier();
		$this->server->getTickSleeper()->addNotifier($notifier, function() : void{
			$this->check();
		});
		$this->instance = new RCONInstance($this->socket, $password, max(1, $maxClients), $this->server->getLogger(), $this->ipcThreadSocket, $notifier);

		socket_getsockname($this->socket, $addr, $port);
		$this->server->getLogger()->info("RCON работает $addr:$port");
	}

	/**
	 * @return void
	 */
	public function stop(){
		$this->instance->close();
		socket_write($this->ipcMainSocket, "\x00"); //make select() return
		$this->instance->quit();

		@socket_close($this->socket);
		@socket_close($this->ipcMainSocket);
		@socket_close($this->ipcThreadSocket);
	}

	/**
	 * @return void
	 */
	public function check(){
		$response = new RemoteConsoleCommandSender();
		$command = $this->instance->cmd;

		$this->server->getPluginManager()->callEvent($ev = new RemoteServerCommandEvent($response, $command));

		if(!$ev->isCancelled()){
			$this->server->dispatchCommand($ev->getSender(), $ev->getCommand());
		}

		$this->instance->response = TextFormat::clean($response->getMessage());
		$this->instance->synchronized(function(RCONInstance $thread) : void{
			$thread->notify();
		}, $this->instance);
	}
}