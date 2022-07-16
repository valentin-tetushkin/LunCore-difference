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

namespace pocketmine\level\loadchunk;

use pocketmine\level\format\Chunk;

interface ChunkManager {
	public function getBlockIdAt(int $x, int $y, int $z) : int;
	public function setBlockIdAt(int $x, int $y, int $z, int $id);
	public function setBlockDataAt(int $x, int $y, int $z, int $data);
	public function getChunk(int $chunkX, int $chunkZ);
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk = null);
	public function getSeed();
	public function getWorldHeight() : int;
}