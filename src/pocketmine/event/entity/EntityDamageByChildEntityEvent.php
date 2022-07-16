<?php

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;

class EntityDamageByChildEntityEvent extends EntityDamageByEntityEvent {
	/** @var int */
	private $childEntityEid;

	public function __construct(Entity $damager, Entity $childEntity, Entity $entity, int $cause, $damage){
		$this->childEntityEid = $childEntity->getId();
		parent::__construct($damager, $entity, $cause, $damage);
	}

	public function getChild(){
		return $this->getEntity()->getLevel()->getServer()->findEntity($this->childEntityEid);
	}
}