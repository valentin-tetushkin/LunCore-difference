<?php

namespace pocketmine\event;

interface Cancellable {
	public function isCancelled();

	/**
	 * @param bool $forceCancel
	 *
	 * @return mixed
	 */
	public function setCancelled($forceCancel = false);
}