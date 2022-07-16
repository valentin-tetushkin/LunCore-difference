<?php


namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class getPosCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"Узнать свой координаты",
			"/getpos"
		);
	}

	public function execute(CommandSender $sender, $currentAlias, array $args){
		$sender->sendMessage("Ваши координаты: {$sender->getFloorX()}, {$sedner->getFloorX()}, {$sender->getFloorZ()}");
		return true;
	}
}