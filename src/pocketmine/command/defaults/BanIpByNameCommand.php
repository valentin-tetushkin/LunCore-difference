<?php


namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;


class BanIpByNameCommand extends VanillaCommand {

	/**
	 * BanIpByNameCommand constructor.
	 *
	 * @param string $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.banipbyname.description",
			"/lbanipbyname <player>"
		);
		$this->setPermission("pocketmine.command.banipbyname");
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
			return \true;
		}

		if(\count($args) === 0){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return \false;
		}

		$name = \array_shift($args);
		$reason = \implode(" ", $args);

		if($sender->getServer()->getPlayer($name) instanceof Player) $target = $sender->getServer()->getPlayer($name);
		else return \false;

		$sender->getServer()->getIPBans()->addBan($target->getAddress(), $reason, \null, $sender->getName());

		if(($player = $sender->getServer()->getPlayerExact($name)) instanceof Player){
			$player->kick($reason !== "" ? "Banned by admin. Reason:" . $reason : "Banned by admin.");
		}

		Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.banipbyname.success", [$player !== \null ? $player->getName() : $name]));

		return \true;
	}
}
