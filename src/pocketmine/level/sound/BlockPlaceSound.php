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

namespace pocketmine\level\sound;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class BlockPlaceSound extends GenericSound {

	protected $data;

	public function __construct(Block $b){
		parent::__construct($b, LevelSoundEventPacket::SOUND_PLACE, 1, $b->getId());
		$this->data = $b->getId();
	}
	
	public function encode(){
		$pk = new LevelSoundEventPacket;
		$pk->sound = $this->id;
		$pk->pitch = 1;
		$pk->extraData = $this->data;
		list($pk->x, $pk->y, $pk->z) = [$this->x, $this->y, $this->z];
		
		return $pk;
	}
}
