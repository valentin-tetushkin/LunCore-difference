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

class PlayerPickupExpOrbEvent extends PlayerEvent implements Cancellable {
	public static $handlerList = null;

	private $amount;

	/**
	 * PlayerPickupExpOrbEvent constructor.
	 *
	 * @param Player $p
	 * @param int    $amount
	 */
	public function __construct(Player $p, int $amount = 0){
		$this->player = $p;
		$this->amount = $amount;
	}

	/**
	 * @return int
	 */
	public function getAmount() : int{
		return $this->amount;
	}

	/**
	 * @param int $amount
	 */
	public function setAmount(int $amount){
		$this->amount = $amount;
	}
}