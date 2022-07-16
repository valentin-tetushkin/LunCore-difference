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
 * Метод устарел будем для вас что-то новое делать
*/

namespace pocketmine\scheduler;

/**
 * Позволяет создавать простые обратные вызовы с дополнительными данными
 * Последним параметром в обратном вызове будет этот объект
 *
 * Если вы хотите выполнить задачу в плагине, рассмотрите возможность расширения PluginTask под свои нужды.
 *
 * @устарело
 * НЕ используйте это больше, оно давно устарело в PocketMine.
 * и будет удалено на каком-то этапе в будущем.
 */

class CallbackTask extends Task {

	/** @var callable */
	protected $callable;

	/** @var array */
	protected $args;

	/**
	 * @param callable $callable
	 * @param array    $args
	 */
	public function __construct(callable $callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
		$this->args[] = $this;
	}

	/**
	 * @return callable
	 */
	public function getCallable(){
		return $this->callable;
	}

	/**
	 * @param $currentTicks
	 */
	public function onRun($currentTicks){
		call_user_func_array($this->callable, $this->args);
	}

}
