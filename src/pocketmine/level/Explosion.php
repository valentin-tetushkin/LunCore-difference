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

use pocketmine\block\Block;
use pocketmine\entity\{Entity, EnderCrystal};
use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\ExplodePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Random;
use pocketmine\tile\{Chest, Furnace, Hopper, BrewingStand, ShulkerBox, Dispenser, Dropper, FlowerPot};
use pocketmine\tile\Container;
use pocketmine\math\Math;
use pocketmine\tile\Tile;
use function ceil;
use function floor;
use function mt_rand;

class Explosion{
	private $rays = 16;
	public $level;
	public $source;
	public $size;
	public $affectedBlocks = [];
	public $stepLen = 0.3;
	private $what;
	private $dropItem;
	private $subChunkHandler;

	public function __construct(Position $center, float $size, $what = null, bool $dropItem = true){
		if(!$center->isValid()) throw new \InvalidArgumentException("Позиция не имеет действительного мира");
		$this->source = $center;
		$this->level = $center->getLevel();

		if($size <= 0) throw new \InvalidArgumentException("Радиус взрыва должен быть больше 0, получил $size");
		$this->size = $size;

		$this->what = $what;
		$this->dropItem = $dropItem;
		$this->subChunkHandler = new SubChunkIteratorManager($this->level, false);
	}

    /**
     * Рассчитывает, какие блоки будут уничтожены этим взрывом. Если взорватьB() вызывается без вызова этого, нет блоков
     * будет уничтожен.
     */
	public function explodeA() : bool{
		if($this->size < 0.1) return false;

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Position(0, 0, 0, $this->level);

		$currentChunk = null;
		$currentSubChunk = null;

		$mRays = $this->rays - 1;
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = $pointerX;
							$y = $pointerY;
							$z = $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;
							
							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;

							if(!$this->subChunkHandler->moveTo($vBlock->x, $vBlock->y, $vBlock->z)) continue;

							$blockId = $this->subChunkHandler->currentSubChunk->getBlockId($vBlock->x & 0x0f, $vBlock->y & 0x0f, $vBlock->z & 0x0f);

							if($blockId !== 0){
								$blastForce -= (Block::$blastResistance[$blockId] / 5 + 0.3) * $this->stepLen;
								if($blastForce > 0){
									if(!isset($this->affectedBlocks[$index = Level::blockHash($vBlock->x, $vBlock->y, $vBlock->z)])) $this->affectedBlocks[$index] = Block::get($blockId, $this->subChunkHandler->currentSubChunk->getBlockData($vBlock->x & 0x0f, $vBlock->y & 0x0f, $vBlock->z & 0x0f), $vBlock);
								}
							}
						}
					}
				}
			}
		}

		return true;
	}

    /**
     * Выполняет эффекты взрыва в мире. Это включает в себя уничтожение блоков (если таковые имеются), нанесение урона и отбрасывание сущностей,
     * и создание звуков и частиц.
     */
	public function explodeB() : bool{
		$send = [];
		$updateBlocks = [];

		$source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
		$yield = (1 / $this->size) * 100;

		if($this->what instanceof Entity){
			$this->level->getServer()->getPluginManager()->callEvent($ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield));
			if($ev->isCancelled()){
				return false;
			}else{
				$yield = $ev->getYield();
				$this->affectedBlocks = $ev->getBlockList();
			}
		}

		$explosionSize = $this->size * 2;
		$minX = (int) floor($this->source->x - $explosionSize - 1);
		$maxX = (int) ceil($this->source->x + $explosionSize + 1);
		$minY = (int) floor($this->source->y - $explosionSize - 1);
		$maxY = (int) ceil($this->source->y + $explosionSize + 1);
		$minZ = (int) floor($this->source->z - $explosionSize - 1);
		$maxZ = (int) ceil($this->source->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
		foreach($list as $entity){
			if(!$entity instanceof EnderCrystal){
				$distance = $entity->distance($this->source) / $explosionSize;

				if($distance <= 1){
					$motion = $entity->subtract($this->source)->normalize();

					$impact = (1 - $distance) * ($exposure = 1);

					$damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

					if($this->what instanceof Entity){
						$ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
					}elseif($this->what instanceof Block){
						$ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
					}else $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);

					if($entity->attack($ev->getFinalDamage(), $ev) === true) $ev->useArmors();
					$entity->setMotion($motion->multiply($impact));
				}
			}
		}

		$air = Item::get(BlockIds::AIR);

		foreach($this->affectedBlocks as $block){
			$yieldDrops = false;
			if($block->getId() === BlockIds::TNT){
				$mot = (new Random())->nextSignedFloat() * M_PI * 2;
				$tnt = Entity::createEntity("PrimedTNT", $this->level, new CompoundTag("", [
					"Pos" => new ListTag("Pos", [
						new DoubleTag("", $block->x + 0.5),
						new DoubleTag("", $block->y),
						new DoubleTag("", $block->z + 0.5)
					]),
					"Motion" => new ListTag("Motion", [
						new DoubleTag("", -sin($mot) * 0.02),
						new DoubleTag("", 0.2),
						new DoubleTag("", -cos($mot) * 0.02)
					]),
					"Rotation" => new ListTag("Rotation", [
						new FloatTag("", 0),
						new FloatTag("", 0)
					]),
					"Fuse" => new ByteTag("Fuse", mt_rand(10, 30))
				]));
				$tnt->spawnToAll();
			}elseif($yieldDrops = (mt_rand(0, 100) < $yield)){
				foreach($block->getDrops($air) as $drop){
					$this->level->dropItem($block->add(0.5, 0.5, 0.5), Item::get(...$drop));
				}
			}

			if($block->getId() !== 56 and $block->getId() !== 129 and $block->getId() !== 57 and $block->getId() !== 7){
				$this->level->setBlockIdAt($block->x, $block->y, $block->z, 0);
				$this->level->setBlockDataAt($block->x, $block->y, $block->z, 0);
			}

			$t = $this->level->getTileAt($block->x, $block->y, $block->z);
			if($t instanceof Tile){
				if($t instanceof Chest){
					$t->unpair();
					foreach($t->getInventory()->getContents() as $item){
						$t->getLevel()->dropItem($t->asVector3(), $item);
					}
				}elseif($t instanceof Furnace or $t instanceof BrewingStand or $t instanceof Hopper or $t instanceof Dispenser or $t instanceof Dropper){
					foreach($t->getInventory()->getContents() as $item){
						$t->getLevel()->dropItem($t->asVector3(), $item);
					}
				}elseif($t instanceof ShulkerBox){
					foreach($t->getInventory()->getContents() as $item){
						$t->getLevel()->dropItem($t->asVector3(), $item);
					}
					$item2 = Item::get(218, 10);
					$t->getLevel()->dropItem($t->asVector3(), $item2);
				}
				$t->close();
			}
		}


		$this->level->addParticle(new HugeExplodeSeedParticle($source));
		$this->level->broadcastLevelSoundEvent($source, LevelSoundEventPacket::SOUND_EXPLODE);

		return true;
	}

	public function explodeC() : bool{
		if($this->size < 0.1) return false;

		$vector = new Vector3(0, 0, 0);
		$vBlock = new Vector3(0, 0, 0);

		$mRays = $this->rays - 1;
		for($i = 0; $i < $this->rays; ++$i){
			for($j = 0; $j < $this->rays; ++$j){
				for($k = 0; $k < $this->rays; ++$k){
					if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
						$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
						$vector->setComponents(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
						$pointerX = $this->source->x;
						$pointerY = $this->source->y;
						$pointerZ = $this->source->z;

						for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
							$x = $pointerX;
							$y = $pointerY;
							$z = $pointerZ;
							$vBlock->x = $pointerX >= $x ? $x : $x - 1;
							$vBlock->y = $pointerY >= $y ? $y : $y - 1;
							$vBlock->z = $pointerZ >= $z ? $z : $z - 1;
							$pointerX += $vector->x;
							$pointerY += $vector->y;
							$pointerZ += $vector->z;
							if($vBlock->y < 0 or $vBlock->y >= Level::Y_MAX) break;
							$block = $this->level->getBlock($vBlock);

							if(($block->getId() !== 0)and($block->getId() != 7)){
								if(!isset($this->affectedBlocks[$index = Level::blockHash($block->x, $block->y, $block->z)])) $this->affectedBlocks[$index] = $block;
							}
						}
					}
				}
			}
		}

		return true;
	}
}