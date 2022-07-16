<?php

namespace pocketmine\entity;


use pocketmine\event\entity\{EntityCombustByEntityEvent, EntityDamageByChildEntityEvent, EntityDamageByEntityEvent, EntityDamageEvent, ProjectileHitEvent};
use pocketmine\item\Potion;
use pocketmine\level\{Level, MovingObjectPosition};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{CompoundTag, ShortTag, DoubleTag};

abstract class Projectile extends Entity{

	const DATA_SHOOTER_ID = 17;

	/** @var float */
	protected $damage = 0.0;

	public $hadCollision = false;

	/**
	 * Projectile constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param Entity|null $shootingEntity
	 */
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		parent::__construct($level, $nbt);
		if($shootingEntity !== null){
			$this->setOwningEntity($shootingEntity);
		}
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

	protected function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(1);
		$this->setHealth(1);
		if(isset($this->namedtag->Age)){
			$this->age = $this->namedtag["Age"];
		}

		if(isset($this->namedtag->damage)){
			$this->damage = $this->namedtag["damage"];
		}
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canCollideWith(Entity $entity){
		return $entity instanceof Living and !$this->onGround;
	}

	/**
	 * Returns the amount of damage this projectile will deal to the entity it hits.
	 * @return int
	 */
	public function getResultDamage() : int{
		return (int) ceil(sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2) * $this->damage);
	}

	public function onCollideWithEntity(Entity $entity){
		$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));

		$damage = $this->getResultDamage();

		if($this->getOwningEntity() === null){
			$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}else{
			$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entity, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}

		if($entity->attack($ev->getFinalDamage(), $ev) === true){
			if($this instanceof Arrow and $this->getPotionId() != 0){
				foreach(Potion::getEffectsById($this->getPotionId() - 1) as $effect){
					$entity->addEffect($effect->setDuration($effect->getDuration() / 8));
				}
			}
			$ev->useArmors();
		}

		$this->hadCollision = true;

		if($this->fireTicks > 0){
			$ev = new EntityCombustByEntityEvent($this, $entity, 5);
			$this->server->getPluginManager()->callEvent($ev);
			if(!$ev->isCancelled()){
				$entity->setOnFire($ev->getDuration());
			}
		}

		$this->close();
	}

	public function canBeCollidedWith() : bool{
		return false;
	}

	/**
	 * Returns the base damage applied on collision. This is multiplied by the projectile's speed to give a result
	 * damage.
	 *
	 * @return float
	 */
	public function getBaseDamage() : float{
		return $this->damage;
	}

	/**
	 * Sets the base amount of damage applied by the projectile.
	 *
	 * @param float $damage
	 */
	public function setBaseDamage(float $damage) : void{
		$this->damage = $damage;
	}

	/**
	 * Called when the projectile hits something. Override this to perform non-target-specific effects when the
	 * projectile hits something.
	 */
	protected function onHit(ProjectileHitEvent $event) : void{

	}

	public function saveNBT(){
		parent::saveNBT();

		$this->namedtag->Age = new ShortTag("Age", $this->age);
		$this->namedtag->damage = new DoubleTag("damage", $this->damage);
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}


		$tickDiff = $currentTick - $this->lastUpdate;
		if($tickDiff <= 0 and !$this->justCreated){
			return true;
		}
		$this->lastUpdate = $currentTick;

		$hasUpdate = $this->entityBaseTick($tickDiff);

		if($this->isAlive()){

			$movingObjectPosition = null;

			if(!$this->isCollided){
				$this->motionY -= $this->gravity;
			}

			$moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

			$list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

			$nearDistance = PHP_INT_MAX;
			$nearEntity = null;

			foreach($list as $entity){
				if(/*!$entity->canCollideWith($this) or */
				($entity->getId() === $this->getOwningEntityId() and $this->ticksLived < 5)
				){
					continue;
				}

				$axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);
				$ob = $axisalignedbb->calculateIntercept($this, $moveVector);

				if($ob === null){
					continue;
				}

				$distance = $this->distanceSquared($ob->hitVector);

				if($distance < $nearDistance){
					$nearDistance = $distance;
					$nearEntity = $entity;
				}
			}

			if($nearEntity !== null){
				$movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
			}

			if($movingObjectPosition !== null){
				if($movingObjectPosition->entityHit !== null){
					$this->onCollideWithEntity($movingObjectPosition->entityHit);
					return false;
				}
			}

			$this->move($this->motionX, $this->motionY, $this->motionZ);

			if($this->isCollided and !$this->hadCollision){ //Collided with a block
				$this->hadCollision = true;

				$this->motionX = 0;
				$this->motionY = 0;
				$this->motionZ = 0;

				$this->server->getPluginManager()->callEvent(new ProjectileHitEvent($this));
				return false;
			}elseif(!$this->isCollided and $this->hadCollision){ //Collided with block, but block later removed
				$this->hadCollision = false;
			}

			if(!$this->hadCollision or abs($this->motionX) > 0.00001 or abs($this->motionY) > 0.00001 or abs($this->motionZ) > 0.00001){
				$f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
				$this->yaw = (atan2($this->motionX, $this->motionZ) * 180 / M_PI);
				$this->pitch = (atan2($this->motionY, $f) * 180 / M_PI);
				$hasUpdate = true;
			}

			$this->updateMovement();
		}

		return $hasUpdate;
	}

}