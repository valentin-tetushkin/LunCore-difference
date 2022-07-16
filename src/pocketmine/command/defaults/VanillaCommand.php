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

abstract class VanillaCommand extends Command {
	const MAX_COORD = 30000000;
	const MIN_COORD = -30000000;

	/**
	 * VanillaCommand constructor.
	 *
	 * @param string $name
	 * @param string $description
	 * @param null   $usageMessage
	 * @param array  $aliases
	 */
	public function __construct($name, $description = "", $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
	}

	/**
	 * @param CommandSender $sender
	 * @param               $value
	 * @param int           $min
	 * @param int           $max
	 *
	 * @return int
	 */
	protected function getInteger(CommandSender $sender, $value, $min = self::MIN_COORD, $max = self::MAX_COORD){
		$i = (int) $value;

		if($i < $min){
			$i = $min;
		}elseif($i > $max){
			$i = $max;
		}

		return $i;
	}

	/**
	 * @param               $original
	 * @param CommandSender $sender
	 * @param               $input
	 * @param int           $min
	 * @param int           $max
	 *
	 * @return float|int
	 */
	protected function getRelativeDouble($original, CommandSender $sender, $input, $min = self::MIN_COORD, $max = self::MAX_COORD){
		if($input[0] === "~"){
			$value = $this->getDouble($sender, substr($input, 1));

			return $original + $value;
		}

		return $this->getDouble($sender, $input, $min, $max);
	}

	/**
	 * @param CommandSender $sender
	 * @param               $value
	 * @param int           $min
	 * @param int           $max
	 *
	 * @return float|int
	 */
	protected function getDouble(CommandSender $sender, $value, $min = self::MIN_COORD, $max = self::MAX_COORD){
		$i = (double) $value;

		if($i < $min){
			$i = $min;
		}elseif($i > $max){
			$i = $max;
		}

		return $i;
	}
}