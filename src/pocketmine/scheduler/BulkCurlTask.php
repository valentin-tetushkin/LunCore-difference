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

use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use function serialize;
use function unserialize;

/**
 * Executes a consecutive list of cURL operations.
 *
 * The result of this AsyncTask is an array of arrays (returned from {@link Internet::simpleCurl}) or InternetException objects.
 */
class BulkCurlTask extends AsyncTask{
	/** @var string */
	private $operations;

	/**
	 * BulkCurlTask constructor.
	 *
	 * $operations accepts an array of arrays. Each member array must contain a string mapped to "page", and optionally,
	 * "timeout", "extraHeaders" and "extraOpts". Documentation of these options are same as those in
	 * {@link Utils::simpleCurl}.
	 *
	 * @param array[] $operations
	 * @param mixed|null $complexData
	 * @phpstan-param list<array{page: string, timeout?: float, extraHeaders?: list<string>, extraOpts?: array<int, mixed>}> $operations
	 */
	public function __construct(array $operations, $complexData = null){
		$this->storeLocal($complexData);
		$this->operations = serialize($operations);
	}

	public function onRun(){
		/** @phpstan-var list<array{page: string, timeout?: float, extraHeaders?: list<string>, extraOpts?: array<int, mixed>}> $operations */
		$operations = unserialize($this->operations);
		$results = [];
		foreach($operations as $op){
			try{
				$results[] = Internet::simpleCurl($op["page"], $op["timeout"] ?? 10, $op["extraHeaders"] ?? [], $op["extraOpts"] ?? []);
			}catch(InternetException $e){
				$results[] = $e;
			}
		}
		$this->setResult($results);
	}
}