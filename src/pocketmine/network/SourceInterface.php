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

/**
 * Классы, связанные с сетью
 */

namespace pocketmine\network;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;

/**
 * Классы, реализующие этот интерфейс, можно будет привязывать к игрокам
 */
interface SourceInterface{

    /**
     * Выполняет действия, необходимые для запуска интерфейса после его регистрации.
     */
	public function start();

    /**
     * Отправляет DataPacket на интерфейс, возвращает уникальный идентификатор пакета, если $needACK имеет значение true
     *
	 * @param Player     $player
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return int
	 */
	public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true);

	/**
	 * Terminates the connection
	 *
	 * @param Player $player
	 * @param string $reason
	 *
	 */
	public function close(Player $player, $reason = "unknown reason");

	/**
	 * @param string $name
	 */
	public function setName(string $name);

    /**
     * Вызывается каждый тик для обработки событий на интерфейсе.
     */
	public function process() : void;

	public function shutdown();

	/**
	 * @deprecated
	 */
	public function emergencyShutdown();

}