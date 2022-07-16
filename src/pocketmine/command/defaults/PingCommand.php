<?php


namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PingCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"Узнать свой пинг",
			"/ping"
		);
	}

	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "Только для игроков!");
			return true;
		}
		
		$sender->sendPing();
		return true;
	}
}