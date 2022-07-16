<?php

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;

class ChunkUnloadEvent extends ChunkEvent implements Cancellable {
	public static $handlerList = null;
}