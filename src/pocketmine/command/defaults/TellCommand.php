<?php

/*
 ╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
 ║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
 ║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
 ║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
 ║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
 ╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TellCommand extends VanillaCommand {

	/**
	 * TellCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.tell.description",
			"%commands.tell.usage",
			["w", "whisper", "msg", "m"]
		);
		$this->setPermission("pocketmine.command.tell");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return false;
		}

		$player = $sender->getServer()->getPlayer(array_shift($args));

		if($player === $sender){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.message.sameTarget"));
			return true;
		}

		if($player instanceof Player){
			$sender->sendMessage("[" . $sender->getName() . " -> " . $player->getDisplayName() . "] " . implode(" ", $args));
			$player->sendMessage("[" . ($sender instanceof Player ? $sender->getDisplayName() : $sender->getName()) . " -> " . $player->getName() . "] " . implode(" ", $args));
		}else{
			$sender->sendMessage(new TranslationContainer("commands.generic.player.notFound"));
		}

		return true;
	}
}
