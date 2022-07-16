<?php

declare(strict_types=1);

namespace pocketmine\sleeper;

use function assert;

class SleeperNotifier extends \Threaded{
	/** @var ThreadedSleeper */
	private $threadedSleeper;

	/** @var int */
	private $sleeperId;

	/** @var bool */
	private $notification = false;

	final public function attachSleeper(ThreadedSleeper $sleeper, int $id) : void{
		$this->threadedSleeper = $sleeper;
		$this->sleeperId = $id;
	}

	final public function getSleeperId() : int{
		return $this->sleeperId;
	}

	/**
	 * Call this method from other threads to wake up the main server thread.
	 */
	final public function wakeupSleeper() : void{
		assert($this->threadedSleeper !== null);

		$this->threadedSleeper->synchronized(function() : void{
			if(!$this->notification){
				$this->notification = true;

				/*
				 * if we didn't synchronize with ThreadedSleeper, the main thread might detect the notification
				 * (notification = true by this point in the code), process and decrement notification count, all before
				 * we got a chance to increment it and wake up the sleeper in the first place, leading to an underflow.
				 */
				$this->threadedSleeper->wakeupNoSync();
			}
		});
	}

	final public function hasNotification() : bool{
		return $this->notification;
	}

	final public function clearNotification() : void{
		/* wakeupSleeper() synchronizes with ThreadedSleeper, we must do the same here. */
		$this->threadedSleeper->synchronized(function() : void{
			$this->threadedSleeper->clearNotificationNoSync();
			$this->notification = false;
		});
	}
}
