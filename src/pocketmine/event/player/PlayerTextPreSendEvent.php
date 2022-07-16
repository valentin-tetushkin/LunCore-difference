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
 * Called when a player is sent a message via sendMessage, sendPopup or sendTip
 */
class PlayerTextPreSendEvent extends PlayerEvent implements Cancellable {
	const MESSAGE = 0;
	const POPUP = 1;
	const TIP = 2;
	const TRANSLATED_MESSAGE = 3;

	public static $handlerList = null;

	protected $message;
	protected $type = self::MESSAGE;

	/**
	 * PlayerTextPreSendEvent constructor.
	 *
	 * @param Player $player
	 * @param        $message
	 * @param int    $type
	 */
	public function __construct(Player $player, $message, $type = self::MESSAGE){
		$this->player = $player;
		$this->message = $message;
		$this->type = $type;
	}

	/**
	 * @return mixed
	 */
	public function getMessage(){
		return $this->message;
	}

	/**
	 * @param $message
	 */
	public function setMessage($message){
		$this->message = $message;
	}

	/**
	 * @return int
	 */
	public function getType(){
		return $this->type;
	}

}
