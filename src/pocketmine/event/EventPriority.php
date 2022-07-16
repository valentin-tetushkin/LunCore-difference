<?php

namespace pocketmine\event;

abstract class EventPriority {
	public const ALL = [
		self::LOWEST,
		self::LOW,
		self::NORMAL,
		self::HIGH,
		self::HIGHEST,
		self::MONITOR
	];

	const LOWEST = 5;

	const LOW = 4;
	
	const NORMAL = 3;
	/**
	 * Event call is of high importance
	 */
	const HIGH = 2;
	/**
	 * Event call is critical and must have the final say in what happens
	 * to the event
	 */
	const HIGHEST = 1;
	/**
	 * Event is listened to purely for monitoring the outcome of an event.
	 *
	 * No modifications to the event should be made under this priority
	 */
	const MONITOR = 0;

	/**
	 * @param string $name
	 *
	 * @return int
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function fromString(string $name) : int{
		$name = strtoupper($name);
		$const = self::class . "::" . $name;
		if($name !== "ALL" and \defined($const)){
			return \constant($const);
		}

		throw new \InvalidArgumentException("Unable to resolve priority \"$name\"");
	}
}