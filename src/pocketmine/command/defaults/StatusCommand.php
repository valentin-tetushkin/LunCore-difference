<?php


/* @author LunCore team
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
 */

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class StatusCommand extends VanillaCommand {

	/**
	 * StatusCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.status.description",
			"%pocketmine.command.status.usage"
		);
		$this->setPermission("pocketmine.command.status");
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

		$rUsage = Utils::getRealMemoryUsage();
		$mUsage = Utils::getMemoryUsage(true);

		$server = $sender->getServer();
		$sender->sendMessage(TextFormat::WHITE . "§l§f---- " . TextFormat::GOLD . "§l§5Статистика Сервера" . TextFormat::WHITE . "§l§f ----");

		$time = (int) (microtime(true) - \pocketmine\START_TIME);

		$seconds = $time % 60;
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(($time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(($time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor($time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days дней "
					: "") . "$hours часов(-а) "
					: "") . "$minutes минут(-ы) "
			: "") . "$seconds секунд(-ы)";

		$sender->sendMessage("§l§fLun§cCore §7- §l§fСколько живет: §5". $uptime);

		$tpsColor = TextFormat::WHITE;
		if($server->getTicksPerSecond() < 17){
			$tpsColor = TextFormat::GOLD;
		}elseif($server->getTicksPerSecond() < 12){
			$tpsColor = TextFormat::RED;
		}

		$sender->sendMessage("§l§f" . "%pocketmine.command.status.CurrentTPS " . "§l§5" . $server->getTicksPerSecond() . " (" . $server->getTickUsage() . "%)");
		$sender->sendMessage("§l§f" . "%pocketmine.command.status.AverageTPS " . "§l§5" . $server->getTicksPerSecondAverage() . " (" . $server->getTickUsageAverage() . "%)");
 
		$onlineCount = 0;
		foreach($sender->getServer()->getOnlinePlayers() as $player){
			if($player->isOnline() and (!($sender instanceof Player) or $sender->canSee($player))){
				++$onlineCount;
			}
		}

		$sender->sendMessage("§l§f". "%pocketmine.command.status.player" . "§l§5" . " " . $onlineCount . "/" . $sender->getServer()->getMaxPlayers());
		$sender->sendMessage("§l§f".  TextFormat::WHITE . "§l§fКоличество ядер: " . "§l§5" . Utils::getCoreCount(true));
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Networkupload " . "§l§5" . \round($server->getNetwork()->getUpload() / 1024, 2) . " kB/s");
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Networkdownload " . "§l§5" . \round($server->getNetwork()->getDownload() / 1024, 2) . " kB/s");
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Threadcount " . "§l§5" . Utils::getThreadCount());
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Mainmemory " . "§l§5" . number_format(round(($mUsage[0] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Totalmemory " . "§l§5" . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Totalvirtualmemory " . "§l§5" . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage("§l§f". "%pocketmine.command.status.Heapmemory " . "§l§5" . number_format(round(($rUsage[0] / 1024) / 1024, 2), 2) . " MB.");

		if($server->getProperty("memory.global-limit") > 0){
			$sender->sendMessage("§l§f". "%pocketmine.command.status.Maxmemorymanager " . "§l§5" . number_format(round($server->getProperty("memory.global-limit"), 2), 2) . " MB.");
		}

		foreach($server->getLevels() as $level){
			$levelName = $level->getFolderName() !== $level->getName() ? " (" . $level->getName() . ")" : "";
			$timeColor = $level->getTickRateTime() > 40 ? TextFormat::RED : TextFormat::YELLOW;
			$sender->sendMessage("§l§f". "Мир \"{$level->getFolderName()}\"$levelName: " .
			"§l§5" . number_format(count($level->getChunks())) . "§l§5" . " %pocketmine.command.status.chunks " .
			"§l§5" . number_format(count($level->getEntities())) . "§l§5" . " %pocketmine.command.status.entities " .
			"§l§5" . number_format(count($level->getTiles())) . "§l§5" . " %pocketmine.command.status.tiles " .
				"%pocketmine.command.status.Time " . round($level->getTickRateTime(), 2) . "ms"
			);
		}

		return true;
	}
}
