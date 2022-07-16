<?php
/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace {
	const INT32_MIN = -0x80000000;
	const INT32_MAX = 0x7fffffff;

	function safe_var_dump(){
		static $cnt = 0;
		foreach(func_get_args() as $var){
			switch(true){
				case is_array($var):
					echo str_repeat("  ", $cnt) . "array(" . count($var) . ") {" . PHP_EOL;
					foreach($var as $key => $value){
						echo str_repeat("  ", $cnt + 1) . "[" . (is_int($key) ? $key : '"' . $key . '"') . "]=>" . PHP_EOL;
						++$cnt;
						safe_var_dump($value);
						--$cnt;
					}
					echo str_repeat("  ", $cnt) . "}" . PHP_EOL;
					break;
				case is_int($var):
					echo str_repeat("  ", $cnt) . "int(" . $var . ")" . PHP_EOL;
					break;
				case is_float($var):
					echo str_repeat("  ", $cnt) . "float(" . $var . ")" . PHP_EOL;
					break;
				case is_bool($var):
					echo str_repeat("  ", $cnt) . "bool(" . ($var === true ? "true" : "false") . ")" . PHP_EOL;
					break;
				case is_string($var):
					echo str_repeat("  ", $cnt) . "string(" . strlen($var) . ") \"$var\"" . PHP_EOL;
					break;
				case is_resource($var):
					echo str_repeat("  ", $cnt) . "resource() of type (" . get_resource_type($var) . ")" . PHP_EOL;
					break;
				case is_object($var):
					echo str_repeat("  ", $cnt) . "object(" . get_class($var) . ")" . PHP_EOL;
					break;
				case is_null($var):
					echo str_repeat("  ", $cnt) . "NULL" . PHP_EOL;
					break;
			}
		}
	}

	function dummy(){

	}
}

namespace pocketmine {

	use pocketmine\utils\{MainLogger, ServerKiller, Terminal, Timezone, Utils};
	use pocketmine\lang\Installer;
	use raklib\RakLib;

	const NAME = "LunCore 1.0.7-Public";
	const VERSION = "1.1.X";
	const API_VERSION = "3.0.1";
	const CODENAME = "vk.com/luncore";
	const GENISYS_API_VERSION = '2.0.0';
	const CORE_VERSION = '1.0.7-Public';

	const MIN_PHP_VERSION = "7.3.0";

	/**
	 * @param string $message
	 * @return void
	 */
	function critical_error($message){
		echo "[Error] $message" . PHP_EOL;
	}


	if(version_compare(MIN_PHP_VERSION, PHP_VERSION) > 0){
		echo "[CRIT] " . \pocketmine\NAME . " requires PHP " . MIN_PHP_VERSION . ", but you have PHP " . PHP_VERSION . "." . PHP_EOL;
		echo "[CRIT] Please use the installer provided on the homepage." . PHP_EOL;
		exit(1);
	}

	if(!extension_loaded("pthreads")){
		echo "[CRIT] Unable to find the pthreads extension." . PHP_EOL;
		echo "[CRIT] Please use the installer provided on the homepage." . PHP_EOL;
		exit(1);
	}

	if(!extension_loaded("phar")){
		echo "[CRIT] Unable to find the Phar extension." . PHP_EOL;
		echo "[CRIT] Please use the installer provided on the homepage, or update to a newer PHP version." . PHP_EOL;
		exit(1);
	}

	if(\Phar::running(true) !== ""){
		define('pocketmine\PATH', \Phar::running(true) . "/");
	}else{
		define('pocketmine\PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
	}

	if(!class_exists("ClassLoader", false)){
		if(!is_file(\pocketmine\PATH . "src/spl/ClassLoader.php")){
			echo "[CRIT] Unable to find the PocketMine-SPL library." . PHP_EOL;
			echo "[CRIT] Please use provided builds or clone the repository recursively." . PHP_EOL;
			exit(1);
		}
		require_once(\pocketmine\PATH . "src/spl/ClassLoader.php");
		require_once(\pocketmine\PATH . "src/spl/BaseClassLoader.php");
	}

	$autoloader = new \BaseClassLoader();
	$autoloader->addPath(\pocketmine\PATH . "src");
	$autoloader->addPath(\pocketmine\PATH . "src" . DIRECTORY_SEPARATOR . "spl");
	$autoloader->register(true);

	error_reporting(-1);

	set_error_handler([Utils::class, 'errorExceptionHandler']);

	if(!class_exists(RakLib::class)){
		echo "[CRIT] Unable to find the RakLib library." . PHP_EOL;
		echo "[CRIT] Please use provided builds or clone the repository recursively." . PHP_EOL;
		exit(1);
	}

	if(version_compare(RakLib::VERSION, "0.9.0") < 0){
		echo "[CRIT] RakLib version 0.9.0 is required, while you have version " . RakLib::VERSION . "." . PHP_EOL;
		echo "[CRIT] Please update your submodules or use provided builds." . PHP_EOL;
		exit(1);
	}

	ini_set("allow_url_fopen", '1');
	ini_set("display_errors", '1');
	ini_set("display_startup_errors", '1');
	ini_set("default_charset", "utf-8");

	ini_set("memory_limit", '-1');

	define('pocketmine\RESOURCE_PATH', \pocketmine\PATH . 'src' . DIRECTORY_SEPARATOR . 'pocketmine' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);

	$opts = getopt("", ["data:", "plugins:", "no-wizard"]);

	define('pocketmine\DATA', isset($opts["data"]) ? $opts["data"] . DIRECTORY_SEPARATOR : realpath(getcwd()) . DIRECTORY_SEPARATOR);
	define('pocketmine\PLUGIN_PATH', isset($opts["plugins"]) ? $opts["plugins"] . DIRECTORY_SEPARATOR : realpath(getcwd()) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR);

	Terminal::init();

	define('pocketmine\ANSI', Terminal::hasFormattingCodes());

	if(!file_exists(\pocketmine\DATA)){
		mkdir(\pocketmine\DATA, 0777, true);
	}

	$lockFile = fopen(\pocketmine\DATA . 'server.lock', "a+b");
	if($lockFile === false){
		critical_error("Unable to open server.lock file. Please check that the current user has read/write permissions to it.");
		exit(1);
	}
	define('pocketmine\LOCK_FILE', $lockFile);
	if(!flock(\pocketmine\LOCK_FILE, LOCK_EX | LOCK_NB)){
		flock(\pocketmine\LOCK_FILE, LOCK_SH);
		$pid = stream_get_contents(\pocketmine\LOCK_FILE);

		echo "[CRIT] Another LunCore instance (PID $pid) is already using this folder (" . realpath(\pocketmine\DATA) . ")." . PHP_EOL;
		echo "[CRIT] Please stop the other server first before running a new one." . PHP_EOL;
		exit(1);
	}
	ftruncate(\pocketmine\LOCK_FILE, 0);
	fwrite(\pocketmine\LOCK_FILE, (string) getmypid());
	fflush(\pocketmine\LOCK_FILE);
	flock(\pocketmine\LOCK_FILE, LOCK_SH);
	$tzError = Timezone::init();

    $logger = new MainLogger(\pocketmine\DATA . "console.log");
    $logger->registerStatic();

	foreach($tzError as $e){
		$logger->warning($e);
	}
	unset($tzError);

	$errors = 0;

	if(PHP_INT_SIZE < 8){
		critical_error("Running " . \pocketmine\NAME . " with 32-bit systems/PHP is no longer supported.");
		critical_error("Please upgrade to a 64-bit system, or use a 64-bit PHP binary if this is a 64-bit system.");
		exit(1);
	}

	if(php_sapi_name() !== "cli"){
		$logger->critical("You must run " . \pocketmine\NAME . " using the CLI.");
		++$errors;
	}

	if(!extension_loaded("sockets")){
		$logger->critical("Unable to find the Socket extension.");
		++$errors;
	}

	$pthreads_version = phpversion("pthreads");
	if(substr_count($pthreads_version, ".") < 2){
		$pthreads_version = "0.$pthreads_version";
	}
	if(version_compare($pthreads_version, "3.1.6") < 0){
		$logger->critical("pthreads >= 3.1.6 is required, while you have $pthreads_version.");
		++$errors;
	}

	if(!extension_loaded("uopz")){
	}

	if(extension_loaded("pocketmine")){
		$logger->critical("The native PocketMine extension is no longer supported.");
		++$errors;
	}

	if(extension_loaded("xdebug")){
		$logger->warning("You are running " . \pocketmine\NAME . " with xdebug enabled. This has a major impact on performance.");
	}

	if(!extension_loaded("pocketmine_chunkutils")){
		$logger->warning("ChunkUtils extension is missing. Anvil-format worlds will experience degraded performance.");
	}

	if(!extension_loaded("curl")){
		$logger->critical("Unable to find the cURL extension.");
		++$errors;
	}

	if(!extension_loaded("yaml")){
		$logger->critical("Unable to find the YAML extension.");
		++$errors;
	}

	if(!extension_loaded("zlib")){
		$logger->critical("Unable to find the Zlib extension.");
		++$errors;
	}

	if($errors > 0){
		$logger->critical("Please update or recompile PHP.");
		$logger->shutdown();
		$logger->join();
		exit(1); //Exit with error
	}

	if(file_exists(\pocketmine\PATH . ".git/HEAD")){ //Found Git information!
		$ref = trim(file_get_contents(\pocketmine\PATH . ".git/HEAD"));
		if(preg_match('/^[0-9a-f]{40}$/i', $ref)){
			define('pocketmine\GIT_COMMIT', strtolower($ref));
		}elseif(substr($ref, 0, 5) === "ref: "){
			$refFile = \pocketmine\PATH . ".git/" . substr(trim(file_get_contents(\pocketmine\PATH . ".git/HEAD")), 5);
			if(is_file($refFile)){
				define('pocketmine\GIT_COMMIT', strtolower(trim(file_get_contents($refFile))));
			}
		}
	}
	if(!defined('pocketmine\GIT_COMMIT')){
		define('pocketmine\GIT_COMMIT', "0000000000000000000000000000000000000000");
	}

	@define("INT32_MASK", is_int(0xffffffff) ? 0xffffffff : -1);
	@ini_set("opcache.mmap_base", bin2hex(random_bytes(8)));
	
	if(!file_exists(\pocketmine\DATA . "server.properties") and !isset($opts["no-wizard"])){
		$installer = new Installer();
		if(!$installer->run()){
			$logger->shutdown();
			$logger->join();
			exit(-1);
		}
	}

	if(\Phar::running(true) === ""){
	}

	define('pocketmine\START_TIME', microtime(true));
	ThreadManager::init();
	new Server($autoloader, $logger, \pocketmine\DATA, \pocketmine\PLUGIN_PATH);

	$logger->debug("Остановка остальных процессов.");

	$killer = new ServerKiller(8);
	$killer->start(PTHREADS_INHERIT_CONSTANTS);
	usleep(10000);

	$logger->shutdown();
	$logger->join();

	echo Terminal::$FORMAT_RESET . PHP_EOL;

	if(!flock(\pocketmine\LOCK_FILE, LOCK_UN)){
		echo "[CRIT] Failed to release the server.lock file.";
	}

	if(!fclose(\pocketmine\LOCK_FILE)){
		echo "[CRIT] Отсутствует класс [server-lock] в главной директории.";
	}

	if(ThreadManager::getInstance()->stopAll() > 0){
		$logger->debug("Some threads could not be stopped, performing a force-kill");
		Utils::kill(getmypid());
	}else{
		exit(0);
	}

}