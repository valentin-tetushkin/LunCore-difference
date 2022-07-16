<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * @author LunCore team
 * @link http://vk.com/luncore
 * @creator vk.com/klainyt
 *
*/

namespace pocketmine\scheduler;

use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;
use pocketmine\Worker;
use function error_reporting;
use function gc_enable;
use function ini_set;
use function set_error_handler;

class AsyncWorker extends Worker{
	/** @var array */
	private static $store = [];

	/** @var \ThreadedLogger */
	private $logger;
	/** @var int */
	private $id;

	/** @var int */
	private $memoryLimit;

	public function __construct(\ThreadedLogger $logger, int $id, int $memoryLimit){
		$this->logger = $logger;
		$this->id = $id;
		$this->memoryLimit = $memoryLimit;
	}

	public function run(){
		error_reporting(-1);

		$this->registerClassLoader();

		//set this after the autoloader is registered
		set_error_handler([Utils::class, 'errorExceptionHandler']);

		if($this->logger instanceof MainLogger){
			$this->logger->registerStatic();
		}

		gc_enable();

		if($this->memoryLimit > 0){
			ini_set('memory_limit', $this->memoryLimit . 'M');
			$this->logger->debug("Set memory limit to " . $this->memoryLimit . " MB");
		}else{
			ini_set('memory_limit', '-1');
			$this->logger->debug("No memory limit set");
		}
	}

	public function getLogger() : \ThreadedLogger{
		return $this->logger;
	}

	/**
	 * @return void
	 */
	public function handleException(\Throwable $e){
		$this->logger->logException($e);
	}

	/**
	 * @return string
	 */
	public function getThreadName(){
		return "Asynchronous Worker #" . $this->id;
	}

	public function getAsyncWorkerId() : int{
		return $this->id;
	}

    /**
     * Сохраняет смешанные данные в локальном хранилище объектов рабочего потока. Это можно использовать для хранения объектов, которые вы
     * хотите использовать в этом рабочем потоке из нескольких AsyncTasks.
     *
	 * @param mixed  $value
	 */
	public function saveToThreadStore(string $identifier, $value) : void{
		self::$store[$identifier] = $value;
	}

    /**
     * Извлекает смешанные данные из локального хранилища объектов рабочего потока.
     *
     * Обратите внимание, что локальное хранилище объектов потока может быть очищено, и ваши данные могут не существовать, поэтому ваш код должен
     * учитывайте возможность того, что то, что вы пытаетесь получить, может не существовать.
     *
     * Объекты, хранящиеся в этом хранилище, могут быть извлечены ТОЛЬКО во время выполнения задачи.
	 *
	 * @return mixed
	 */
	public function getFromThreadStore(string $identifier){
		return self::$store[$identifier] ?? null;
	}

    /**
     * Удаляет ранее сохраненные смешанные данные из локального хранилища объектов рабочего потока.
     */
	public function removeFromThreadStore(string $identifier) : void{
		unset(self::$store[$identifier]);
	}
}