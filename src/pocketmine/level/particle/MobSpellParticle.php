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

namespace pocketmine\level\particle;

use pocketmine\math\Vector3;

class MobSpellParticle extends GenericParticle {
	/**
	 * MobSpellParticle constructor.
	 *
	 * @param Vector3 $pos
	 * @param int     $r
	 * @param int     $g
	 * @param int     $b
	 * @param int     $a
	 */
	public function __construct(Vector3 $pos, $r = 0, $g = 0, $b = 0, $a = 255){
		parent::__construct($pos, Particle::TYPE_MOB_SPELL, (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff));
	}
}