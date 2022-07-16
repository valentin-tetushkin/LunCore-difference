<?php

/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\command;

use pocketmine\command\defaults\BanCidByNameCommand;
use pocketmine\command\defaults\BanCidCommand;
use pocketmine\command\defaults\BanCommand;
use pocketmine\command\defaults\BanIpByNameCommand;
use pocketmine\command\defaults\BanIpCommand;
use pocketmine\command\defaults\BanListCommand;
use pocketmine\command\defaults\BiomeCommand;
use pocketmine\command\defaults\CaveCommand;
use pocketmine\command\defaults\ChunkInfoCommand;
use pocketmine\command\defaults\DefaultGamemodeCommand;
use pocketmine\command\defaults\DeopCommand;
use pocketmine\command\defaults\DifficultyCommand;
use pocketmine\command\defaults\DumpMemoryCommand;
use pocketmine\command\defaults\EffectCommand;
use pocketmine\command\defaults\EnchantCommand;
use pocketmine\command\defaults\FillCommand;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\command\defaults\GarbageCollectorCommand;
use pocketmine\command\defaults\GiveCommand;
use pocketmine\command\defaults\HelpCommand;
use pocketmine\command\defaults\KickCommand;
use pocketmine\command\defaults\KillCommand;
use pocketmine\command\defaults\ListCommand;
use pocketmine\command\defaults\LvdatCommand;
use pocketmine\command\defaults\MeCommand;
use pocketmine\command\defaults\OpCommand;
use pocketmine\command\defaults\PardonCidCommand;
use pocketmine\command\defaults\PardonCommand;
use pocketmine\command\defaults\PardonIpCommand;
use pocketmine\command\defaults\ParticleCommand;
use pocketmine\command\defaults\PluginsCommand;
use pocketmine\command\defaults\PingCommand;
use pocketmine\command\defaults\ReloadCommand;
use pocketmine\command\defaults\SaveCommand;
use pocketmine\command\defaults\SaveOffCommand;
use pocketmine\command\defaults\SaveOnCommand;
use pocketmine\command\defaults\SayCommand;
use pocketmine\command\defaults\SeedCommand;
use pocketmine\command\defaults\SetBlockCommand;
use pocketmine\command\defaults\SetWorldSpawnCommand;
use pocketmine\command\defaults\SpawnpointCommand;
use pocketmine\command\defaults\StatusCommand;
use pocketmine\command\defaults\StopCommand;
use pocketmine\command\defaults\SummonCommand;
use pocketmine\command\defaults\TeleportCommand;

use pocketmine\command\defaults\TransferServerCommand;

use pocketmine\command\defaults\TellCommand;
use pocketmine\command\defaults\TimeCommand;
use pocketmine\command\defaults\TimingsCommand;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\defaults\VersionCommand;
use pocketmine\command\defaults\WeatherCommand;
use pocketmine\command\defaults\WhitelistCommand;
use pocketmine\command\defaults\XpCommand;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

use pocketmine\command\defaults\MakeServerCommand;
use pocketmine\command\defaults\ExtractPluginCommand;
use pocketmine\command\defaults\ExtractPharCommand;
use pocketmine\command\defaults\MakePluginCommand;
use pocketmine\command\defaults\LoadPluginCommand;

class SimpleCommandMap implements CommandMap {

	/**
	 * @var Command[]
	 */
	protected $knownCommands = [];

	/**
	 * @var bool[]
	 */
	protected $commandConfig = [];

	/** @var Server */
	private $server;

	/**
	 * SimpleCommandMap constructor.
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server){
		$this->server = $server;
		/** @var bool[] */
		$this->commandConfig = $this->server->getProperty("commands");
		$this->setDefaultCommands();
	}

	private function setDefaultCommands(){
		$this->register("pocketmine", new WeatherCommand("lweather"));

		$this->register("pocketmine", new BanCidCommand("lbancid"));
		$this->register("pocketmine", new PardonCidCommand("lpardoncid"));
		$this->register("pocketmine", new BanCidByNameCommand("lbancidbyname"));
		$this->register("pocketmine", new BanIpByNameCommand("lbanipbyname"));

		$this->register("pocketmine", new ExtractPharCommand("lextractphar"));
		$this->register("pocketmine", new ExtractPluginCommand("lep"));
		$this->register("pocketmine", new MakePluginCommand("lmp"));
		$this->register("pocketmine", new MakeServerCommand("lms"));
		$this->register("pocketmine", new LoadPluginCommand("lloadplugin"));

		$this->register("pocketmine", new LvdatCommand("llvdat"));
		$this->register("pocketmine", new BiomeCommand("lbiome"));
		$this->register("pocketmine", new CaveCommand("lcave"));
		$this->register("pocketmine", new ChunkInfoCommand("lchunkinfo"));

		$this->register("pocketmine", new VersionCommand("lversion"));
		$this->register("pocketmine", new FillCommand("lfill"));
		$this->register("pocketmine", new PluginsCommand("lplugins"));
		$this->register("pocketmine", new SeedCommand("lseed"));
		$this->register("pocketmine", new HelpCommand("lhelp"), null, true);
		$this->register("pocketmine", new StopCommand("lstop"), null, true);
		$this->register("pocketmine", new TellCommand("ltell"));
		$this->register("pocketmine", new DefaultGamemodeCommand("ldefaultgamemode"));
		$this->register("pocketmine", new BanCommand("lban"));
		$this->register("pocketmine", new BanIpCommand("lban-ip"));
		$this->register("pocketmine", new BanListCommand("lbanlist"));
		$this->register("pocketmine", new PardonCommand("lpardon"));
		$this->register("pocketmine", new PardonIpCommand("lpardon-ip"));
		$this->register("pocketmine", new SayCommand("lsay"));
		$this->register("pocketmine", new MeCommand("lme"));
		$this->register("pocketmine", new ListCommand("llist"));
		$this->register("pocketmine", new DifficultyCommand("ldifficulty"));
		$this->register("pocketmine", new KickCommand("lkick"));
		$this->register("pocketmine", new OpCommand("lop"));
		$this->register("pocketmine", new DeopCommand("ldeop"));
		$this->register("pocketmine", new WhitelistCommand("lwhitelist"));
		$this->register("pocketmine", new SaveOnCommand("save-on"));
		$this->register("pocketmine", new SaveOffCommand("save-off"));
		$this->register("pocketmine", new SaveCommand("save-all"), null, true);
		$this->register("pocketmine", new GiveCommand("lgive"));
		$this->register("pocketmine", new EffectCommand("leffect"));
		$this->register("pocketmine", new EnchantCommand("lenchant"));
		$this->register("pocketmine", new ParticleCommand("lparticle"));
		$this->register("pocketmine", new PingCommand("ping"));
		$this->register("pocketmine", new GamemodeCommand("lgamemode"));
		$this->register("pocketmine", new KillCommand("lkill"));
		$this->register("pocketmine", new SpawnpointCommand("lspawnpoint"));
		$this->register("pocketmine", new SetWorldSpawnCommand("lsetworldspawn"));
		$this->register("pocketmine", new SummonCommand("lsummon"));
		$this->register("pocketmine", new TeleportCommand("ltp"));

		$this->register("pocketmine", new TransferServerCommand("ltransfer"));

		$this->register("pocketmine", new TimeCommand("ltime"));
		$this->register("pocketmine", new TimingsCommand("ltimings"));
		$this->register("pocketmine", new ReloadCommand("lreload"), null, true);
		$this->register("pocketmine", new XpCommand("lxp"));
		$this->register("pocketmine", new SetBlockCommand("lsetblock"));

		$this->register("pocketmine", new StatusCommand("status"), null, true);
		$this->register("pocketmine", new GarbageCollectorCommand("lgc"), null, true);
		$this->register("pocketmine", new DumpMemoryCommand("ldumpmemory"), null, true);
	}


	/**
	 * @param string $fallbackPrefix
	 * @param array  $commands
	 */
	public function registerAll($fallbackPrefix, array $commands){
		foreach($commands as $command){
			$this->register($fallbackPrefix, $command);
		}
	}

	/**
	 * @param string  $fallbackPrefix
	 * @param Command $command
	 * @param null    $label
	 * @param bool    $overrideConfig
	 *
	 * @return bool
	 */
	public function register($fallbackPrefix, Command $command, $label = null, $overrideConfig = false){
		if($label === null){
			$label = $command->getName();
		}
		$label = strtolower(trim($label));

		//Check if command was disabled in config and for override
		if(!(($this->commandConfig[$label] ?? $this->commandConfig["default"] ?? true) or $overrideConfig)){
			return false;
		}
		$fallbackPrefix = strtolower(trim($fallbackPrefix));

		$registered = $this->registerAlias($command, false, $fallbackPrefix, $label);

		$aliases = $command->getAliases();
		foreach($aliases as $index => $alias){
			if(!$this->registerAlias($command, true, $fallbackPrefix, $alias)){
				unset($aliases[$index]);
			}
		}
		$command->setAliases($aliases);

		if(!$registered){
			$command->setLabel($fallbackPrefix . ":" . $label);
		}

		$command->register($this);

		return $registered;
	}

	/**
	 * @param Command $command
	 * @param         $isAlias
	 * @param         $fallbackPrefix
	 * @param         $label
	 *
	 * @return bool
	 */
	private function registerAlias(Command $command, $isAlias, $fallbackPrefix, $label){
		$this->knownCommands[$fallbackPrefix . ":" . $label] = $command;
		if(($command instanceof VanillaCommand or $isAlias) and isset($this->knownCommands[$label])){
			return false;
		}

		if(isset($this->knownCommands[$label]) and $this->knownCommands[$label]->getLabel() !== null and $this->knownCommands[$label]->getLabel() === $label){
			return false;
		}

		if(!$isAlias){
			$command->setLabel($label);
		}

		$this->knownCommands[$label] = $command;

		return true;
	}

	/**
	 * Returns a command to match the specified command line, or null if no matching command was found.
	 * This method is intended to provide capability for handling commands with spaces in their name.
	 * The referenced parameters will be modified accordingly depending on the resulting matched command.
	 *
	 * @param string   $commandName reference parameter
	 * @param string[] $args reference parameter
	 *
	 * @return Command|null
	 */
	public function matchCommand(string &$commandName, array &$args){
		$count = min(count($args), 255);

		for($i = 0; $i < $count; ++$i){
			$commandName .= array_shift($args);
			if(($command = $this->getCommand($commandName)) instanceof Command){
				return $command;
			}

			$commandName .= " ";
		}

		return null;
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param               $label
	 * @param array         $args
	 * @param int           $offset
	 */
	private function dispatchAdvanced(CommandSender $sender, Command $command, $label, array $args, $offset = 0){
		if(isset($args[$offset])){
			$argsTemp = $args;
			switch($args[$offset]){
				case "@a":
					$p = $this->server->getOnlinePlayers();
					if(count($p) <= 0){
						$sender->sendMessage(TextFormat::RED . "No players online"); //TODO: add language
					}else{
						foreach($p as $player){
							$argsTemp[$offset] = $player->getName();
							$this->dispatchAdvanced($sender, $command, $label, $argsTemp, $offset + 1);
						}
					}
					break;
				case "@r":
					$players = $this->server->getOnlinePlayers();
					if(count($players) > 0){
						$argsTemp[$offset] = $players[array_rand($players)]->getName();
						$this->dispatchAdvanced($sender, $command, $label, $argsTemp, $offset + 1);
					}
					break;
				case "@p":
					if($sender instanceof Player){
						$argsTemp[$offset] = $sender->getName();
						$this->dispatchAdvanced($sender, $command, $label, $argsTemp, $offset + 1);
					}else{
						$sender->sendMessage(TextFormat::RED . "You must be a player!"); //TODO: add language
					}
					break;
				default:
					$this->dispatchAdvanced($sender, $command, $label, $argsTemp, $offset + 1);
			}
		}else $command->execute($sender, $label, $args);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLine
	 *
	 * @return bool
	 */
	public function dispatch(CommandSender $sender, $commandLine){
		$args = explode(" ", $commandLine);

		if(count($args) === 0){
			return false;
		}

		$sentCommandLabel = strtolower(array_shift($args));
		$target = $this->getCommand($sentCommandLabel);

		if($target === null){
			return false;
		}

		$target->timings->startTiming();
		try{
			if($this->server->advancedCommandSelector){
				$this->dispatchAdvanced($sender, $target, $sentCommandLabel, $args);
			}else{
				$target->execute($sender, $sentCommandLabel, $args);
			}
		}catch(\Throwable $e){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.exception"));
			$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.command.exception", [$commandLine, (string) $target, $e->getMessage()]));
			$logger = $sender->getServer()->getLogger();
			if($logger instanceof MainLogger){
				$logger->logException($e);
			}
		}
		$target->timings->stopTiming();

		return true;
	}

	public function unregister(Command $command){
		foreach($this->knownCommands as $lbl => $cmd){
			if($cmd === $command){
				unset($this->knownCommands[$lbl]);
			}
		}

		$command->unregister($this);
		
		return true;
	}

	public function clearCommands(){
		foreach($this->knownCommands as $command){
			$command->unregister($this);
		}
		$this->knownCommands = [];
		$this->setDefaultCommands();
	}

	/**
	 * @param string $name
	 *
	 * @return null|Command
	 */
	public function getCommand($name){
		if(isset($this->knownCommands[$name])){
			return $this->knownCommands[$name];
		}

		return null;
	}

	/**
	 * @return Command[]
	 */
	public function getCommands(){
		return $this->knownCommands;
	}


	/**
	 * @return void
	 */
	public function registerServerAliases(){
		$values = $this->server->getCommandAliases();

		foreach($values as $alias => $commandStrings){
			if(strpos($alias, ":") !== false or strpos($alias, " ") !== false){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.illegal", [$alias]));
				continue;
			}

			$targets = [];
			$bad = [];
			$recursive = [];

			foreach($commandStrings as $commandString){
				$args = explode(" ", $commandString);
				$commandName = "";
				$command = $this->matchCommand($commandName, $args);

				if($command === null){
					$bad[] = $commandString;
				}elseif(strcasecmp($commandName, $alias) === 0){
					$recursive[] = $commandString;
				}else{
					$targets[] = $commandString;
				}
			}

			if(count($recursive) > 0){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.recursive", [$alias, implode(", ", $recursive)]));
				continue;
			}

			if(count($bad) > 0){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.notFound", [$alias, implode(", ", $bad)]));
				continue;
			}

			//These registered commands have absolute priority
			if(count($targets) > 0){
				$this->knownCommands[strtolower($alias)] = new FormattedCommandAlias(strtolower($alias), $targets);
			}else{
				unset($this->knownCommands[strtolower($alias)]);
			}

		}
	}
}