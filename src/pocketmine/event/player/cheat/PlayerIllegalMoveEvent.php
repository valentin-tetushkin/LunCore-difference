<?php

namespace pocketmine\event\player\cheat;

use pocketmine\event\Cancellable;
use pocketmine\Player;
use pocketmine\math\Vector3;

class PlayerIllegalMoveEvent extends PlayerCheatEvent implements Cancellable {
	public static $handlerList = null;

	private $attemptedPosition;

	/**
	 * PlayerIllegalMoveEvent constructor.
	 *
	 * @param Player  $player
	 * @param Vector3 $attemptedPosition
	 */
	public function __construct(Player $player, Vector3 $attemptedPosition){
		$this->attemptedPosition = $attemptedPosition;
		$this->player = $player;
	}

	/**
	 * @return Vector3
	 */
	public function getAttemptedPosition() : Vector3{
		return $this->attemptedPosition;
	}

}