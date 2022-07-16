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

namespace pocketmine\plugin;

use pocketmine\event\Event;
use pocketmine\event\Listener;

class MethodEventExecutor implements EventExecutor {

	private $method;

	/**
     * Конструктор MethodEventExecutor.
	 *
	 * @param $method
	 */
	public function __construct($method){
		$this->method = $method;
	}

	/**
	 * @param Listener $listener
	 * @param Event    $event
	 */
	public function execute(Listener $listener, Event $event){
		$listener->{$this->getMethod()}($event);
	}

	/**
	 * @return mixed
	 */
	public function getMethod(){
		return $this->method;
	}
}