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

namespace pocketmine\scheduler;

use pocketmine\plugin\Plugin;

/**
 * Базовый класс для задач плагина. Позволяет серверу легко удалять их при необходимости
 */
abstract class PluginTask extends Task {

	/** @var Plugin */
	protected $owner;

	/**
	 * @param Plugin $owner
	 */
	public function __construct(Plugin $owner){
		$this->owner = $owner;
	}

	/**
	 * @return Plugin
	 */
	public final function getOwner(){
		return $this->owner;
	}

}
