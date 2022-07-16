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

namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;

abstract class MetadataValue {
	/** @var \WeakRef<Plugin> */
	protected $owningPlugin;

	/**
     * Конструктор MetadataValue.
	 *
	 * @param Plugin $owningPlugin
	 */
	protected function __construct(Plugin $owningPlugin){
		$this->owningPlugin = new \WeakRef($owningPlugin);
	}

	/**
	 * @return Plugin
	 */
	public function getOwningPlugin(){
		return $this->owningPlugin->get();
	}

	/**
     * Извлекает значение этого элемента метаданных.
	 *
	 * @return mixed
	 */
	public abstract function value();

    /**
     * Делает этот элемент метаданных недействительным, заставляя его пересчитываться при следующем
     * доступ.
     */
	public abstract function invalidate();
}