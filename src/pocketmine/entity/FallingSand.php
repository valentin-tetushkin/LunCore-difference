<?php


/* @author LunCore team
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

namespace pocketmine\entity;

use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\Flowable;
use pocketmine\block\SnowLayer;
use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class FallingSand extends Entity {
	const NETWORK_ID = 66;

	public $width = 0.98;
	public $length = 0.98;
	public $height = 0.98;

	protected $baseOffset = 0.49;

	protected $gravity = 0.04;
	protected $drag = 0.02;
	protected $blockId = 0;
	protected $damage;

	public $canCollide = false;

	protected function initEntity(){
		parent::initEntity();
		if(isset($this->namedtag->TileID)){
			$this->blockId = $this->namedtag["TileID"];
		}elseif(isset($this->namedtag->Tile)){
			$this->blockId = $this->namedtag["Tile"];
			$this->namedtag["TileID"] = new IntTag("TileID", $this->blockId);
		}

		if(isset($this->namedtag->Data)){
			$this->damage = $this->namedtag["Data"];
		}

		if($this->blockId === 0){
			$this->close();
			return;
		}

		$this->setDataProperty(self::DATA_VARIANT, self::DATA_TYPE_INT, $this->getBlock() | ($this->getDamage() << 8));
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canCollideWith(Entity $entity){
		return false;
	}

	public function canBeMovedByCurrents() : bool{
		return false;
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool|void
	 */
	public function attack($damage, EntityDamageEvent $source){
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($damage, $source);
		}
	}

	/**
	 * @param $tickDiff
	 *
	 * @return bool
	 */
	public function entityBaseTick($tickDiff = 1){
		if($this->closed){
			return false;
		}

		$height = $this->fallDistance;

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->isAlive()){
			$pos = (new Vector3($this->x - $this->width / 2, $this->y, $this->z - $this->width / 2))->floor();

			if($this->onGround){
				$this->kill();
				$block = $this->level->getBlock($pos);
				if(!$block->canBeReplaced() or ($this->onGround and abs($this->y - $this->getFloorY()) > 0.001)){
					//FIXME: anvils are supposed to destroy torches
					$this->getLevel()->dropItem($this, ItemItem::get($this->getBlock(), $this->getDamage()));
				}else{
					if($block instanceof SnowLayer){
						$oldDamage = $block->getDamage();
						$this->server->getPluginManager()->callEvent($ev = new EntityBlockChangeEvent($this, $block, Block::get($this->getBlock(), $this->getDamage() + $oldDamage)));
					}else{
						$this->server->getPluginManager()->callEvent($ev = new EntityBlockChangeEvent($this, $block, Block::get($this->getBlock(), $this->getDamage())));
					}

					if(!$ev->isCancelled()){
						$this->getLevel()->setBlock($pos, $ev->getTo(), true);
						if($ev->getTo() instanceof Anvil){
							$sound = new AnvilFallSound($this);
							$this->getLevel()->addSound($sound);
							foreach($this->level->getNearbyEntities($this->boundingBox->grow(0.1, 0.1, 0.1), $this) as $entity){
								$entity->scheduleUpdate();
								if(!$entity->isAlive()){
									continue;
								}
								if($entity instanceof Living){
									$damage = ($height - 1) * 2;
									if($damage > 40) $damage = 40;
									$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_FALL, $damage, 0.1);
									$entity->attack($damage, $ev);
								}
							}

						}
					}
				}
				$hasUpdate = true;
			}
		}

		return $hasUpdate;
	}

	/**
	 * @return int
	 */
	public function getBlock(){
		return $this->blockId;
	}

	/**
	 * @return mixed
	 */
	public function getDamage(){
		return $this->damage;
	}

	public function saveNBT(){
		parent::saveNBT();
		$this->namedtag->TileID = new IntTag("TileID", $this->blockId);
		$this->namedtag->Data = new ByteTag("Data", $this->damage);
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = FallingSand::NETWORK_ID;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}
