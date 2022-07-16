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

use pocketmine\utils\Utils;

/**
 * ПРЕДУПРЕЖДЕНИЕ! Задачи, созданные плагинами, ДОЛЖНЫ расширять PluginTask.
 */
abstract class Task{

	/** @var TaskHandler */
	private $taskHandler = null;

	/**
	 * @return TaskHandler|null
	 */
	public final function getHandler(){
		return $this->taskHandler;
	}

	public final function getTaskId() : int{
		if($this->taskHandler !== null){
			return $this->taskHandler->getTaskId();
		}

		return -1;
	}

	public function getName() : string{
        try {
            return Utils::getNiceClassName($this);
        } catch (\ReflectionException $e) {
        }
    }

	/**
	 * @return void
	 */
	public final function setHandler($taskHandler){
		if($this->taskHandler === null or $taskHandler === null){
			$this->taskHandler = $taskHandler;
		}
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public abstract function onRun($currentTick);

	/**
	 * Actions to execute if the Task is cancelled
	 *
	 * @return void
	 */
	public function onCancel(){

	}
}