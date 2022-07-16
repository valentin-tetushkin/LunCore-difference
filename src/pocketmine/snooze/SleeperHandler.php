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

use function count;
use function microtime;

/**
 * Управляет спящим потоком, от которого можно ждать уведомлений. Вызывает обратные вызовы для прикрепленных уведомителей, когда
 * уведомления получены от нотификаторов.
 */
class SleeperHandler{
	/** @var \Threaded */
	private $sharedObject;

	/**
	 * @var \Closure[]
	 * @phpstan-var array<int, \Closure() : void>
	 */
	private $notifiers = [];

	/** @var int */
	private $nextSleeperId = 0;

	public function __construct(){
		$this->sharedObject = new \Threaded();
	}

	/**
	 * @param \Closure $handler Called when the notifier wakes the server up, of the signature `function() : void`
	 * @phpstan-param \Closure() : void $handler
	 */
	public function addNotifier(SleeperNotifier $notifier, \Closure $handler) : void{
		$id = $this->nextSleeperId++;
		$notifier->attachSleeper($this->sharedObject, $id);
		$this->notifiers[$id] = $handler;
	}

    /**
     * Удаляет уведомитель из спящего. Обратите внимание, что это не мешает уведомителю разбудить спящего — это просто
     * останавливает уведомитель, получающий действия, обработанные из основного потока.
     */
	public function removeNotifier(SleeperNotifier $notifier) : void{
		unset($this->notifiers[$notifier->getSleeperId()]);
	}

	private function sleep(int $timeout) : void{
		$this->sharedObject->synchronized(function(int $timeout) : void{
			if($this->sharedObject->count() === 0){
				$this->sharedObject->wait($timeout);
			}
		}, $timeout);
	}

    /**
     * Спит до заданной метки времени. Сон может быть прерван уведомлениями, которые будут обработаны перед переходом
     * снова спать.
     */
	public function sleepUntil(float $unixTime) : void{
		while(true){
			$this->processNotifications();

			$sleepTime = (int) (($unixTime - microtime(true)) * 1000000);
			if($sleepTime > 0){
				$this->sleep($sleepTime);
			}else{
				break;
			}
		}
	}

    /**
     * Блокирует до получения уведомлений, а затем обрабатывает уведомления. Не будет спать, если уведомления
     * уже жду.
     */
	public function sleepUntilNotification() : void{
		$this->sleep(0);
		$this->processNotifications();
	}

    /**
     * Обрабатывает любые уведомления от уведомителей и вызывает обработчики полученных уведомлений.
     */
	public function processNotifications() : void{
		while(true){
			$notifierIds = $this->sharedObject->synchronized(function() : array{
				$notifierIds = [];
				foreach($this->sharedObject as $notifierId => $_){
					$notifierIds[$notifierId] = $notifierId;
					unset($this->sharedObject[$notifierId]);
				}
				return $notifierIds;
			});
			if(count($notifierIds) === 0){
				break;
			}
			foreach($notifierIds as $notifierId){
				if(!isset($this->notifiers[$notifierId])){
					//a previously-removed notifier might still be sending notifications; ignore them
					continue;
				}
				$this->notifiers[$notifierId]();
			}
		}
	}
}