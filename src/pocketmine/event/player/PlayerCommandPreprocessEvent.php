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

namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Called when a player runs a command or chats, early in the process
 *
 * You don't want to use this except for a few cases like logging commands,
 * blocking commands on certain places, or applying modifiers.
 *
 * The message contains a slash at the start
 */
class PlayerCommandPreprocessEvent extends PlayerEvent implements Cancellable {
	public static $handlerList = null;

	/** @var string */
	protected $message;


	/**
	 * @param Player $player
	 * @param string $message
	 */
	public function __construct(Player $player, $message){
		$this->player = $player;
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getMessage(){
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage($message){
		$this->message = $message;
	}

	/**
	 * @param Player $player
	 */
	public function setPlayer(Player $player){
		$this->player = $player;
	}

}