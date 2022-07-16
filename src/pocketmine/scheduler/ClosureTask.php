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
 * Реализация задачи, которая позволяет планировщику вызывать замыкания.
 *
 * Пример использования:
 *
 * ```
 * TaskScheduler->scheduleTask (новый ClosureTask (функция ($ currentTick) : void {
 * echo "Привет на $currentTick\n";
 * });
 * ```
 */
class ClosureTask extends Task{

	/**
	 * @var \Closure
	 * @phpstan-var \Closure(int) : void
	 */
	private $closure;

	/**
	 * @param \Closure $closure Must accept only ONE parameter, $currentTick
	 * @phpstan-param \Closure(int) : void $closure
	 */
	public function __construct(\Closure $closure){
		$this->closure = $closure;
	}

	public function getName() : string{
        try {
            return Utils::getNiceClosureName($this->closure);
        } catch (\ReflectionException $e) {
        }
    }

	public function onRun($currentTick){
		($this->closure)($currentTick);
	}
}