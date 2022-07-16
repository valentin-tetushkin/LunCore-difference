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

declare(strict_types=1);

namespace pocketmine\level\utils;

use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\EmptySubChunk;
use pocketmine\level\format\SubChunk;

class SubChunkIteratorManager{
	public $level;
	public $currentChunk;
	public $currentSubChunk;
	protected $currentX;
	protected $currentY;
	protected $currentZ;
	protected $allocateEmptySubs = true;

	public function __construct(ChunkManager $level, bool $allocateEmptySubs = true){
		$this->level = $level;
		$this->allocateEmptySubs = $allocateEmptySubs;
	}

	public function moveTo(int $x, int $y, int $z) : bool{
		if($this->currentChunk === null or $this->currentX !== ($x >> 4) or $this->currentZ !== ($z >> 4)){
			$this->currentX = $x >> 4;
			$this->currentZ = $z >> 4;
			$this->currentSubChunk = null;

			$this->currentChunk = $this->level->getChunk($this->currentX, $this->currentZ);
			if($this->currentChunk === null){
				return false;
			}
		}

		if($this->currentSubChunk === null or $this->currentY !== ($y >> 4)){
			$this->currentY = $y >> 4;

			$this->currentSubChunk = $this->currentChunk->getSubChunk($y >> 4, $this->allocateEmptySubs);
			if($this->currentSubChunk instanceof EmptySubChunk){
				return false;
			}
		}

		return true;
	}

	public function invalidate() : void{
		$this->currentChunk = null;
		$this->currentSubChunk = null;
	}
}