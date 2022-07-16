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

namespace pocketmine\inventory;

interface TransactionQueue {

	const DEFAULT_ALLOWED_RETRIES = 5;

	/**
	 * @return Inventory
	 */
	function getInventories();

	/**
	 * @return \SplQueue
	 */
	function getTransactions();

	/**
	 * @return int
	 */
	function getTransactionCount();

	/**
	 * @param Transaction $transaction
	 *
	 * Adds a transaction to the queue
	 */
	function addTransaction(Transaction $transaction);

	/**
	 * Handles transaction queue execution
	 */
	function execute();

}