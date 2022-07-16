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

namespace pocketmine\level;

use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;

/**
 * Если вы хотите, чтобы чанки загружались и получали уведомления в определенной области,
 * расширить этот класс и зарегистрировать его в Level. Это также будет отмечать куски.
 *
 * Уровень регистрации->registerChunkLoader($this, $chunkX, $chunkZ)
 * Unregister Level->unregisterChunkLoader($this, $chunkX, $chunkZ)
 *
 * ВНИМАНИЕ: При перемещении этого объекта по миру или его уничтожении
 * обязательно освободите существующие ссылки от уровня, иначе будет утечка памяти.
 */
interface ChunkLoader {

	public function getLoaderId();
	public function getPosition();
	public function getX();
	public function getZ();
	public function getLevel();
	public function onChunkChanged(Chunk $chunk);
	public function onChunkLoaded(Chunk $chunk);
	public function onChunkUnloaded(Chunk $chunk);
	public function onChunkPopulated(Chunk $chunk);
	public function onBlockChanged(Vector3 $block);

}