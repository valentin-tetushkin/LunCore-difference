<?php




namespace pocketmine\command\defaults;


use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\nbt\tag\StringTag;

class LvdatCommand extends VanillaCommand {

	/**
	 * LvdatCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.lvdat.description",
			"/lvdat <level-name> <opts|help>"
		);
		$this->setPermission("pocketmine.command.lvdat");
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
			return false;
		}
		$levname = array_shift($args);
		if($levname == ""){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));
			return false;
		}
		if(!$this->autoLoad($sender, $levname)){
			$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.nofound", [$levname]));
			return false;
		}
		$level = $sender->getServer()->getLevelByName($levname);
		if(!$level){
			$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.nofound", [$levname]));
			return false;
		}
		/** @var BaseLevelProvider $provider */
		$provider = $level->getProvider();
		$o = array_shift($args);
		$p = array_shift($args);
		switch($o){
			case "fixname":
				$provider->getLevelData()->LevelName = new StringTag("LevelName", $level->getFolderName());
				$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.fixname", [$level->getFolderName()]));
				break;
			case "help":
				$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));
				$sender->sendMessage("/lvdat %commands.generic.level fixname");
				$sender->sendMessage("/lvdat %commands.generic.level seed %commands.generic.seed");
				$sender->sendMessage("/lvdat %commands.generic.level name %commands.generic.name");
				$sender->sendMessage("/lvdat %commands.generic.level generator %commands.generic.generator");
				$sender->sendMessage("/lvdat %commands.generic.level preset %pocketmine.command.lvdat.preset");
				break;
			case "seed":
				if($p == ""){
					$sender->sendMessage("%commands.generic.opt.missing");
					return false;
				}
				$provider->setSeed($p);
				$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.changed", [$level->getFolderName(), $o]));
				break;
			case "name":
				if($p == ""){
					$sender->sendMessage("%commands.generic.opt.missing");
					return false;
				}
				$provider->getLevelData()->LevelName = new StringTag("LevelName", $p);
				$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.changed", [$level->getFolderName(), $o]));
				break;
			case "generator":
				if($p == ""){
					$sender->sendMessage("%commands.generic.opt.missing");
					return false;
				}
				$provider->getLevelData()->generatorName = new StringTag("generatorName", $p);
				$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.changed", [$level->getFolderName(), $o]));
				break;
			case "preset":
				if($p == ""){
					$sender->sendMessage("%commands.generic.opt.missing");
					return false;
				}
				$provider->getLevelData()->generatorOptions = new StringTag("generatorOptions", $p);
				$sender->sendMessage(new TranslationContainer("pocketmine.command.lvdat.changed", [$level->getFolderName(), $o]));
				break;
			default:
				$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));
				return false;
		}
		$provider->saveLevelData();
		return true;
	}

	/**
	 * @param CommandSender $c
	 * @param               $world
	 *
	 * @return bool
	 */
	public function autoLoad(CommandSender $c, $world){
		if($c->getServer()->isLevelLoaded($world)) return true;
		if(!$c->getServer()->isLevelGenerated($world)){
			return false;
		}
		$c->getServer()->loadLevel($world);
		return $c->getServer()->isLevelLoaded($world);
	}
}
