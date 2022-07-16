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

namespace pocketmine;

use const PTHREADS_INHERIT_ALL;

/**
 * Этот класс должен быть расширен всеми пользовательскими классами потоков.
 */
abstract class Worker extends \Worker{

	/** @var \ClassLoader|null */
	protected $classLoader;

	/** @var bool */
	protected $isKilled = false;

	/**
	 * @return \ClassLoader|null
	 */
	public function getClassLoader(){
		return $this->classLoader;
	}

	/**
	 * @return void
	 */
	public function setClassLoader(\ClassLoader $loader = null){
		if($loader === null){
			$loader = Server::getInstance()->getLoader();
		}
		$this->classLoader = $loader;
	}

    /**
     * Регистрирует загрузчик классов для этого потока.
     *
     * ПРЕДУПРЕЖДЕНИЕ: Этот метод ДОЛЖЕН вызываться из метода run() любого потока-потомка, чтобы можно было использовать автозагрузку.
     * Если вы этого не сделаете, вы не сможете использовать новые классы, которые не были загружены при запуске потока
     * (если вы не используете собственный автозагрузчик).
     */
	public function registerClassLoader(){
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}
	}

	/**
	 * @return bool
	 */
	public function start(int $options = PTHREADS_INHERIT_ALL){
		ThreadManager::getInstance()->add($this);

		if($this->getClassLoader() === null){
			$this->setClassLoader();
		}

		return parent::start($options);
	}

    /**
     * Останавливает поток наилучшим образом. Попробуйте остановить это самостоятельно, прежде чем вызывать это.
     *
     * @возврат недействителен
     */
	public function quit(){
		$this->isKilled = true;

		if(!$this->isShutdown()){
			while($this->unstack() !== null);
			$this->notify();
			$this->shutdown();
		}

		ThreadManager::getInstance()->remove($this);
	}

	/**
	 * @return string
	 */
	public function getThreadName(){
		return (new \ReflectionClass($this))->getShortName();
	}
}