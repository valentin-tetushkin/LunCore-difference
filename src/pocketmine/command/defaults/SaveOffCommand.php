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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;


class SaveOffCommand extends VanillaCommand {

	/**
	 * SaveOffCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.saveoff.description",
			"%pocketmine.command.saveoff.usage"
		);
		$this->setPermission("pocketmine.command.save.disable");
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

		$sender->getServer()->setAutoSave(false);

		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.save.disabled"));

		return true;
	}
}