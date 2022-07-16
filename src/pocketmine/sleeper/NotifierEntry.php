<?php

declare(strict_types=1);

namespace pocketmine\sleeper;

/**
 * @internal
 * Encapsulates the components of a registered sleeper
 * @see SleeperHandler
 */
final class NotifierEntry{

	/** @var SleeperNotifier */
	private $notifier;
	/**
	 * @var callable
	 * @phpstan-var callable() : void
	 */
	private $callback;

	/**
	 * @phpstan-param callable() : void $callback
	 */
	public function __construct(SleeperNotifier $notifier, callable $callback){
		$this->notifier = $notifier;
		$this->callback = $callback;
	}

	public function getNotifier() : SleeperNotifier{ return $this->notifier; }

	/**
	 * @phpstan-return callable() : void
	 */
	public function getCallback() : callable{ return $this->callback; }
}