<?php

namespace pocketmine\event\server;

use pocketmine\command\CommandSender;

class RemoteServerCommandEvent extends ServerCommandEvent {
	public static $handlerList = null;

	/**
	 * @param CommandSender $sender
	 * @param string        $command
	 */
	public function __construct(CommandSender $sender, $command){
		parent::__construct($sender, $command);
	}

}