<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\permission;

use pocketmine\utils\MainLogger;

class BanEntry {
	public static $format = "Y-m-d H:i:s O";

	private $name;
	/** @var \DateTime */
	private $creationDate;
	private $source = "(Unknown)";
	/** @var \DateTime */
	private $expirationDate = null;
	private $reason = "Вы были забанены!.";

	/**
	 * BanEntry constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		$this->name = strtolower($name);
		$this->creationDate = new \DateTime();
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreated(){
		return $this->creationDate;
	}

	/**
	 * @param \DateTime $date
	 */
	public function setCreated(\DateTime $date){
		self::validateDate($date);
		$this->creationDate = $date;
	}

	/**
	 * @return string
	 */
	public function getSource(){
		return $this->source;
	}

	/**
	 * @param $source
	 */
	public function setSource($source){
		$this->source = $source;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpires(){
		return $this->expirationDate;
	}

    /**
     * @param \DateTime|null $date
     */
	public function setExpires(\DateTime $date = null){
		if($date !== null){
			self::validateDate($date);
		}
		$this->expirationDate = $date;
	}

	/**
	 * @return bool
	 */
	public function hasExpired(){
		$now = new \DateTime();

		return !($this->expirationDate === null) && $this->expirationDate < $now;
	}

	/**
	 * @return string
	 */
	public function getReason(){
		return $this->reason;
	}

	/**
	 * @param $reason
	 */
	public function setReason($reason){
		$this->reason = $reason;
	}

	/**
	 * @return string
	 */
	public function getString(){
		$str = "";
		$str .= $this->getName();
		$str .= "|";
		$str .= $this->getCreated()->format(self::$format);
		$str .= "|";
		$str .= $this->getSource();
		$str .= "|";
		$str .= $this->getExpires() === null ? "Forever" : $this->getExpires()->format(self::$format);
		$str .= "|";
		$str .= $this->getReason();

		return $str;
	}

    /**
     * Хакерская функция для проверки объектов \DateTime из-за ошибки в PHP. format() с "Y" может испускать годы с более чем
     * 4 цифры, но createFromFormat() с "Y" не принимает их, если год содержит более 4 цифр.
     *
     * @param \DateTime $dateTime
     * @throws \RuntimeException, если аргумент не может быть проанализирован из отформатированной строки даты
     */
	private static function validateDate(\DateTime $dateTime) : void{
		self::parseDate($dateTime->format(self::$format));
	}

	/**
	 * @param string $date
	 *
	 * @return \DateTime
	 * @throws \RuntimeException
	 */
	private static function parseDate(string $date) : \DateTime{
		$datetime = \DateTime::createFromFormat(self::$format, $date);
		if(!($datetime instanceof \DateTime)){
			throw new \RuntimeException("Error parsing date for BanEntry: " . implode(", ", \DateTime::getLastErrors()["errors"]));
		}

		return $datetime;
	}

	/**
	 * @param string $str
	 *
	 * @return BanEntry
	 * @throws \RuntimeException
	 */
	public static function fromString(string $str) : ?BanEntry{
		if(strlen($str) < 2){
			return null;
		}else{
			$str = explode("|", trim($str));
			$entry = new BanEntry(trim(array_shift($str)));
			do{
				if(empty($str)){
					break;
				}

				$entry->setCreated(self::parseDate(array_shift($str)));
				if(empty($str)){
					break;
				}
			    $entry->setSource(trim(array_shift($str)));
				if(empty($str)){
					break;
				}

				$expire = trim(array_shift($str));
				if(strtolower($expire) !== "forever" and strlen($expire) > 0){
					$entry->setExpires(self::parseDate($expire));
				}
				if(empty($str)){
					break;
				}

				$entry->setReason(trim(array_shift($str)));
			}while(false);

			return $entry;
		}
	}
}