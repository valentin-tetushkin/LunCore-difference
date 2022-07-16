<?php


/*
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

declare(strict_types = 1);

namespace pocketmine\level;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class MovingObjectPosition {

	public $typeOfHit;

	public $blockX;
	public $blockY;
	public $blockZ;
	//public $sideHit;
	public $hitVector;
	public $entityHit = null;

	protected function __construct(){

	}

	public static function fromBlock($x, $y, $z, $side, Vector3 $hitVector){
		$ob = new MovingObjectPosition;
		$ob->typeOfHit = 0;
		$ob->blockX = $x;
		$ob->blockY = $y;
		$ob->blockZ = $z;
		$ob->hitVector = $hitVector->asVector3();
		return $ob;
	}

	public static function fromEntity(Entity $entity){
		$ob = new MovingObjectPosition;
		$ob->typeOfHit = 1;
		$ob->entityHit = $entity;
		$ob->hitVector = $entity->asVector3();
		return $ob;
	}
}
