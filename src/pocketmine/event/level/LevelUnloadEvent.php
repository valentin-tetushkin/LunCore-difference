<?php

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;

class LevelUnloadEvent extends LevelEvent implements Cancellable {
	public static $handlerList = null;
}