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

declare(strict_types=1);

namespace pocketmine\snooze;

use function assert;

/**
 * Notifiers — это Threaded объекты, которые могут быть присоединены к threaded sleepers, чтобы разбудить их.
 */
class SleeperNotifier extends \Threaded{
	/** @var \Threaded */
	private $sharedObject;

	/** @var int */
	private $sleeperId;

	final public function attachSleeper(\Threaded $sharedObject, int $id) : void{
		$this->sharedObject = $sharedObject;
		$this->sleeperId = $id;
	}

	final public function getSleeperId() : int{
		return $this->sleeperId;
	}

    /**
     * Вызовите этот метод из других потоков, чтобы разбудить основной поток сервера.
     */
	final public function wakeupSleeper() : void{
		$shared = $this->sharedObject;
		assert($shared !== null);
		$sleeperId = $this->sleeperId;
		$shared->synchronized(function() use ($shared, $sleeperId) : void{
			if(!isset($shared[$sleeperId])){
				$shared[$sleeperId] = $sleeperId;
				$shared->notify();
			}
		});
	}
}