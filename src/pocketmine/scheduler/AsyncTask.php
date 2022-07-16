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

namespace pocketmine\scheduler;

use pocketmine\Collectable;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use function is_scalar;
use function is_string;
use function serialize;
use function unserialize;

/**
 * Класс, используемый для запуска асинхронных задач в других потоках.
 *
 * AsyncTask не имеет собственного потока. Он ставится в очередь в AsyncPool и выполняется, если есть асинхронный рабочий процесс.
 * без запущенной AsyncTask. Поэтому AsyncTask НЕ ДОЛЖЕН выполняться дольше нескольких секунд. Для задач, которые
 * работать долго или бесконечно, вместо этого запустить другой поток.
 *
 * ПРЕДУПРЕЖДЕНИЕ. Любые объекты, не являющиеся потоками, БУДУТ СЕРИАЛИЗОВАТЬСЯ при назначении членам AsyncTasks или другого объекта Threaded.
 * При последующем доступе из указанного объекта Threaded вы будете работать с КОПИЕЙ ОБЪЕКТА, А НЕ ОРИГИНАЛЬНЫМ ОБЪЕКТОМ.
 * Если вы хотите сохранить несериализуемые объекты для доступа после завершения задачи, сохраните их с помощью
 * {@link AsyncTask::storeLocal}.
 *
 * ПРЕДУПРЕЖДЕНИЕ. Начиная с pthreads v3.1.6, массивы преобразуются в объекты Volatile, если они назначаются членами объектов Threaded.
 * Имейте это в виду при использовании массивов, хранящихся как члены вашей AsyncTask.
 *
 * ПРЕДУПРЕЖДЕНИЕ: Не вызывайте методы API LunCore из других потоков!!
 */
abstract class AsyncTask extends Collectable{
	/**
	 * @var \SplObjectStorage|null
	 * @phpstan-var \SplObjectStorage<AsyncTask, mixed>
     * Используется для хранения объектов в основном потоке, которые не должны сериализоваться.
	 */
	private static $threadLocalStorage;

	/** @var AsyncWorker $worker */
	public $worker = null;

	/** @var \Threaded */
	public $progressUpdates;

	/** @var scalar|null */
	private $result = null;
	/** @var bool */
	private $serialized = false;
	/** @var bool */
	private $cancelRun = false;
	/** @var int|null */
	private $taskId = null;

	/** @var bool */
	private $crashed = false;

	private $isGarbage = false;

	/**
	 * @return bool
	 */
	public function isGarbage() : bool{
		return $this->isGarbage;
	}

	public function setGarbage(){
		$this->isGarbage = true;
	}

	/**
	 * @return void
	 */
	public function run(){
		$this->result = null;
		$this->isGarbage = false;

		if(!$this->cancelRun){
			try{
				$this->onRun();
			}catch(\Throwable $e){
				$this->crashed = true;
				$this->worker->handleException($e);
			}
		}

		$this->setGarbage();
	}

	/**
	 * @return bool
	 */
	public function isCrashed(){
		return $this->crashed or $this->isTerminated();
	}

	/**
	 * @return mixed
	 */
	public function getResult(){
		if($this->serialized){
			if(!is_string($this->result)) throw new AssumptionFailedError("Result expected to be a serialized string");
			return unserialize($this->result);
		}
		return $this->result;
	}

	/**
	 * @return void
	 */
	public function cancelRun(){
		$this->cancelRun = true;
	}

	public function hasCancelledRun() : bool{
		return $this->cancelRun === true;
	}

	public function hasResult() : bool{
		return $this->result !== null;
	}

	/**
	 * @param mixed $result
	 *
	 * @return void
	 */
    public function setResult($result){
        $this->result = ($this->serialized = !is_scalar($result)) ? serialize($result) : $result;
	}

	/**
	 * @return void
	 */
	public function setTaskId(int $taskId){
		$this->taskId = $taskId;
	}

	/**
	 * @return int|null
	 */
	public function getTaskId(){
		return $this->taskId;
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::getFromThreadStore()
	 *
	 * @return mixed
	 */
	public function getFromThreadStore(string $identifier){
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects stored in AsyncWorker thread-local storage can only be retrieved during task execution");
		}
		return $this->worker->getFromThreadStore($identifier);
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::saveToThreadStore()
	 *
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function saveToThreadStore(string $identifier, $value){
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be added to AsyncWorker thread-local storage during task execution");
		}
		$this->worker->saveToThreadStore($identifier, $value);
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::removeFromThreadStore()
	 */
	public function removeFromThreadStore(string $identifier) : void{
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be removed from AsyncWorker thread-local storage during task execution");
		}
		$this->worker->removeFromThreadStore($identifier);
	}

    /**
     * Действия для выполнения при запуске
     *
	 * @return void
	 */
	public abstract function onRun();

	/**
     * Действия, выполняемые после завершения (в основном потоке)
     * Реализуйте это, если хотите обрабатывать данные в своей AsyncTask после их обработки.
     *
	 * @return void
	 */
	public function onCompletion(Server $server){

	}

    /**
     * Вызовите этот метод из {@link AsyncTask::onRun} (поток выполнения AsyncTask), чтобы запланировать вызов
     * {@link AsyncTask::onProgressUpdate} из основного потока с заданным параметром прогресса.
     *
     * @parammixed $progress Значение, которое можно безопасно сериализовать().
     *
     * @возврат недействителен
     */
	public function publishProgress($progress){
		$this->progressUpdates[] = serialize($progress);
	}

    /**
     * @internal Только вызов из AsyncPool.php в основном потоке
     *
	 * @param Server $server
	 */
	public function checkProgressUpdates(Server $server){
		while($this->progressUpdates->count() !== 0){
			$progress = $this->progressUpdates->shift();
			$this->onProgressUpdate($server, unserialize($progress));
		}
	}

    /**
     * Вызывается из основного потока после вызова {@link AsyncTask#publishProgress}.
     * Все вызовы {@link AsyncTask#publishProgress} должны приводить к вызовам {@link AsyncTask#onProgressUpdate} до
     * Вызывается {@link AsyncTask#onCompletion}.
     *
     * @param Сервер $сервер
     * @param \Threaded|mixed $progress Параметр, переданный в {@link AsyncTask#publishProgress}. Если это не
     * Объект с резьбой, он будет сериализован(), а затем десериализован(), как если бы он
     * был клонирован.
     */
	public function onProgressUpdate(Server $server, $progress){

	}

    /**
     * Сохраняет смешанные данные в локальном хранилище потока в родительском потоке. Вы можете использовать это для сохранения ссылок на объекты
     * или массивы, к которым вам нужно получить доступ в {@link AsyncTask#onCompletion}, которые не могут быть сохранены как свойство
     * ваша задача (из-за того, что они сериализуются).
     *
     * Скалярные типы можно хранить непосредственно в свойствах класса вместо использования этого хранилища.
     *
     * ВНИМАНИЕ: ЭТОТ МЕТОД ДОЛЖЕН ВЫЗЫВАТЬСЯ ТОЛЬКО ИЗ ОСНОВНОГО ПОТОКА!
     *
     * @param Mixed $complexData данные для хранения
     *
     * @throws \BadMethodCallException при вызове из любого потока, кроме основного потока
     */
	protected function storeLocal($complexData){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be stored from the parent thread");
		}

		if(self::$threadLocalStorage === null){
			self::$threadLocalStorage = new \SplObjectStorage(); //lazy init
		}

		if(isset(self::$threadLocalStorage[$this])){
			throw new \InvalidStateException("Already storing complex data for this async task");
		}
		self::$threadLocalStorage[$this] = $complexData;
	}

    /**
     * Возвращает данные, ранее сохраненные в локальном хранилище потока в родительском потоке. Используйте это во время обновлений прогресса или
     * завершение задачи для извлечения данных, которые вы сохранили, используя {@link AsyncTask::storeLocal}.
     *
     * ВНИМАНИЕ: ЭТОТ МЕТОД ДОЛЖЕН ВЫЗЫВАТЬСЯ ТОЛЬКО ИЗ ОСНОВНОГО ПОТОКА!
     *
     * @возврат смешанный
     *
     * @throws \RuntimeException, если этот экземпляр AsyncTask не сохранил никаких данных.
     * @throws \BadMethodCallException при вызове из любого потока, кроме основного потока
     */
	protected function fetchLocal(){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be retrieved from the parent thread");
		}

		if(self::$threadLocalStorage === null or !isset(self::$threadLocalStorage[$this])){
			throw new \InvalidStateException("No complex data stored for this async task");
		}

		return self::$threadLocalStorage[$this];
	}

    /**
     * @устарело
     * @см. AsyncTask::fetchLocal()
     *
     * @возврат смешанный
     *
     * @throws \RuntimeException, если данные не были сохранены этим экземпляром AsyncTask
     * @throws \BadMethodCallException при вызове из любого потока, кроме основного потока
     */
	protected function peekLocal(){
		return $this->fetchLocal();
	}

    /**
     * @internal Вызывается AsyncPool для уничтожения любых оставшихся сохраненных объектов, которые эта задача не смогла получить.
     */
	public function removeDanglingStoredObjects() : void{
		if(self::$threadLocalStorage !== null and isset(self::$threadLocalStorage[$this])){
			unset(self::$threadLocalStorage[$this]);
		}
	}
}