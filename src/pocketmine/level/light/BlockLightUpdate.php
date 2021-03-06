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

namespace pocketmine\level\light;

class BlockLightUpdate extends LightUpdate{

	public function getLight(int $x, int $y, int $z) : int{
		return $this->subChunkHandler->currentSubChunk->getBlockLight($x & 0x0f, $y & 0x0f, $z & 0x0f);
	}
	
	public function setLight(int $x, int $y, int $z, int $level){
		$this->subChunkHandler->currentSubChunk->setBlockLight($x & 0x0f, $y & 0x0f, $z & 0x0f, $level);
	}
}