<?php

declare(strict_types=1);

namespace pocketmine\sleeper;

use function assert;

/**
 * Notifiable Threaded class which tracks counts of notifications it receives.
 */
class ThreadedSleeper extends \Threaded{
	/**
	 * @var int
	 */
	private $notifCount = 0;

	/**
	 * Called from the main thread to wait for notifications, or until timeout.
	 *
	 * @param int $timeout defaults to 0 (no timeout, wait indefinitely)
	 */
	public function sleep(int $timeout = 0) : void{
		$this->synchronized(function(int $timeout) : void{
			assert($this->notifCount >= 0, "notification count should be >= 0, got $this->notifCount");
			if($this->notifCount === 0){
				$this->wait($timeout);
			}
		}, $timeout);
	}

	/**
	 * @internal
	 * Called by SleeperNotifier to send a notification to the main thread.
	 * This MUST be called while synchronized with this object.
	 */
	public function wakeupNoSync() : void{
		++$this->notifCount;
		$this->notify();
	}

	/**
	 * @internal
	 * Called by SleeperNotifier to decrement refcount when notifications are cleared.
	 * This MUST be called while synchronized with this object.
	 */
	public function clearNotificationNoSync() : void{
		--$this->notifCount;
	}

	public function hasNotifications() : bool{
		//don't need to synchronize here, pthreads automatically locks/unlocks
		return $this->notifCount > 0;
	}
}
