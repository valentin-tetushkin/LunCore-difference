<?php

namespace pocketmine\event\entity;

use pocketmine\event\Event;

abstract class EntityEvent extends Event {
	/** @var \pocketmine\entity\Entity */
	protected $entity;

	/**
	 * @return \pocketmine\entity\Entity
	 */
	public function getEntity(){
		return $this->entity;
	}
}