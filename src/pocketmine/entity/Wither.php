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

use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\Explosion;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\nbt\tag\{CompoundTag, IntTag, FloatTag, ListTag, StringTag, IntArrayTag, DoubleTag, ShortTag};
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\Particle;

class Wither extends Animal {
	const NETWORK_ID = 52;

	public $width = 1;
	public $length = 6;
	public $height = 4;

	public $dropExp = [25, 50];
	private $boomTicks = 0;
	private $step = 0.2;
	private $motionVector = null;
	private $farest = null;
	private $attackTicks = 0;
	/**
	 * @return string
	 */
	public function getName() : string{
		return "Wither";
	}

	public function initEntity(){
		$this->setMaxHealth(300);
		$this->setHealth(300);
		parent::initEntity();
	}
	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Wither::NETWORK_ID;
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

	//TODO: 添加出生和死亡情景

	/**
	 * @return array
	 */
	public function getDrops(){
        return [ItemItem::get(ItemIds::NETHER_STAR)];
	}
	public function getWitherSkullNBT() : CompoundTag{
        return Entity::createBaseNBT($this->add(0, 2), new Vector3(0, 0, 0), $this->yaw, $this->pitch);
    }
	public function onUpdate($currentTick){
		if($this->isClosed() or !$this->isAlive()) return parent::onUpdate($currentTick);
		
		if($this->isMorph) return true;
		++$this->age;
		if($this->age < 50) return;
		elseif($this->age === 50){
			$explosion = new Explosion($this, 4);
			$explosion->explodeC();
			$explosion->explodeB();
			$explosion->explodeA();
			$this->getLevel()->addParticle(new HugeExplodeSeedParticle($this));
		}
		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
        if($this->getLevel() !== null) $block = $this->getLevel()->getBlock(new Vector3(floor($this->x), floor($this->y) - 1, floor($this->z)));
        else return false;
		
		if($this->attackTicks > 0) --$this->attackTicks;
		
		$x = 0;
		$y = 0;
		$z = 0;
		
		if($this->isOnGround()){
			if($this->fallDistance > 0){
				$this->updateFallState($this->fallDistance, true);
			}else{
				if($this->willMove()){
					foreach($this->getViewers() as $viewer){
						if(($viewer instanceof Player)){
							if($this->distance($viewer) < 20){
								if($this->farest == null) $this->farest = $viewer;
							
								if($this->farest !== $viewer){
									if($this->distance($viewer) < $this->distance($this->farest)) $this->farest = $viewer;
								}
							}
						}
					}
					if($this->farest != null){
						if(($this->farest instanceof Player)and($this->farest->isSurvival())and($this->distance($this->farest) < 20)){
							$this->motionVector = $this->farest->asVector3();
						}else{
							$this->farest = null;
							$this->motionVector = null;
						}
					}
					
					if($this->farest != null){
						if($this->distance($this->farest) < 2){
							if($this->attackTicks == 0){
								$damage = 15;
								$ev = new EntityDamageByEntityEvent($this, $this->farest, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
								if($this->farest->attack($damage, $ev) == true) $ev->useArmors();
								$this->farest->addEffect(Effect::getEffect(20)->setDuration(120)->setAmplifier(1)->setVisible(true));
								$this->attackTicks = 10;
							}
						}elseif($this->distance($this->farest) > 1 and $this->distance($this->farest) < 15 and mt_rand(1, 15) === 15){
							$nbt = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $this->x),new DoubleTag("", $this->y + $this->getEyeHeight() + 3),new DoubleTag("", $this->z)]),"Motion" => new ListTag("Motion", [new DoubleTag("", -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),new DoubleTag("", -sin($this->pitch / 180 * M_PI)),new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))]),"Rotation" => new ListTag("Rotation", [new FloatTag("", ($this->yaw > 180 ? 360 : 0) - $this->yaw),new FloatTag("", -$this->pitch)])]);
							$skull = Entity::createEntity("WitherSkull", $this->getLevel(), $nbt, $this, 1.5 == 2);
							$skull->spawnToAll();
						}
					}
					
					if(($this->motionVector == null)or($this->distance($this->motionVector) < $this->step)){
						$rx = mt_rand(-5, 5);
						$rz = mt_rand(-5, 5);
						$this->motionVector = new Vector3($this->x + $rx, $this->y, $this->z + $rz);
					}else{
						$this->motionVector->y = $this->y;
						if(($this->motionVector->x - $this->x) > $this->step){
							$x = $this->step;
						}elseif(($this->motionVector->x - $this->x) < -$this->step){
							$x = -$this->step;
						}
						if(($this->motionVector->z - $this->z) > $this->step){
							$z = $this->step;
						}elseif(($this->motionVector->z - $this->z) < -$this->step){
							$z = -$this->step;
						}
						
						$bx = floor($this->x);
						$by = floor($this->y);
						$bz = floor($this->z);
						if($x > 0){
							++$bx;
						}elseif($x < 0){
							--$bx;
						}
						if($y > 0){
							++$by;
						}elseif($y < 0){
							--$by;
						}
						if($z > 0){
							++$bz;
						}elseif($z < 0){
							--$bz;
						}
						$block1 = new Vector3($bx, $by, $bz);
						$block2 = new Vector3($bx, $by + 1, $bz);
						if(($this->isInsideOfWater())or($this->level->isFullBlock($block1) && !$this->level->isFullBlock($block2))){
							if($x > 0){
								$x = $x + 0.05;
							}elseif($x < 0){
								$x = $x - 0.05;
							}
							if($z > 0){
								$z = $z + 0.05;
							}elseif($z < 0){
								$z = $z - 0.05;
							}
							$this->move(0, 1.5, 0);
						}elseif($this->level->isFullBlock($block1) && $this->level->isFullBlock($block2)) $this->motionVector = null;
						
						$this->yaw = $this->getMyYaw($x, $z);
						$nextPos = new Vector3($this->x + $x, $this->y, $this->z + $z);
						$latestPos = new Vector3($this->x, $this->y, $this->z);
						$this->pitch = $this->getMyPitch($latestPos, $nextPos);
					}
				}
			}
		}
		
		if((($x != 0)or($y != 0)or($z != 0))and($this->motionVector != null)) $this->setMotion(new Vector3($x, $y, $z));
		
		$this->timings->stopTiming();

		return $hasUpdate;
	}
}
