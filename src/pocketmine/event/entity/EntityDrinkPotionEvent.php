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

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\item\Potion;

class EntityDrinkPotionEvent extends EntityEvent implements Cancellable {

	public static $handlerList = null;

	/* @var Potion */
	private $potion;

	/* @var Effect[] */
	private $effects;

	/**
	 * EntityDrinkPotionEvent constructor.
	 *
	 * @param Entity $entity
	 * @param Potion $potion
	 */
	public function __construct(Entity $entity, Potion $potion){
		$this->entity = $entity;
		$this->potion = $potion;
		$this->effects = $potion->getEffects();
	}

	/**
	 * @return array|Effect[]
	 */
	public function getEffects(){
		return $this->effects;
	}

	/**
	 * @return Potion
	 */
	public function getPotion(){
		return $this->potion;
	}
}
