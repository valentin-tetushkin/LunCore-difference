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

namespace pocketmine\nbt;


use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\NamedTag;

class JsonNBTParser{

    /**
     * Разбирает NBT в формате JSON в CompoundTag и возвращает его. Используется для разбора тегов, переданных с помощью команды /give.
     *
	 * @param string $data
	 * @param int    &$offset
	 *
	 * @return CompoundTag|null
	 *
	 * @throws \Exception
	 */
	public static function parseJSON(string $data, int &$offset = 0){
		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data[$offset];
			if($c === "{"){
				++$offset;
				$data = self::parseCompound($data, $offset);

				return new CompoundTag("", $data);
			}elseif($c !== " " and $c !== "\r" and $c !== "\n" and $c !== "\t"){
                throw new \Exception("Синтаксическая ошибка: неожиданная "$c" по смещению $offset");
			}
		}

		return null;
	}

	/**#
	 * @param string $str
	 * @param int    &$offset
	 *
	 * @return NamedTag[]
	 */
	private static function parseList(string $str, int &$offset = 0) : array{
		$len = strlen($str);

		$key = 0;
		$value = null;

		$data = [];

		for(; $offset < $len; ++$offset){
			if($str[$offset - 1] === "]"){
				break;
			}elseif($str[$offset] === "]"){
				++$offset;
				break;
			}

            try {
                $value = self::readValue($str, $offset, $type);
            } catch (\Exception $e) {
            }

            $tag = NBT::createTag($type);
			if($tag instanceof NamedTag){
				$tag->setValue($value);
				$data[$key] = $tag;
			}

			$key++;
		}

		return $data;
	}

	/**
	 * @param string $str
	 * @param int    $offset
	 *
	 * @return NamedTag[]
	 */
	private static function parseCompound(string $str, int &$offset = 0) : array{
		$len = strlen($str);

		$data = [];

		for(; $offset < $len; ++$offset){
			if($str[$offset - 1] === "}"){
				break;
			}elseif($str[$offset] === "}"){
				++$offset;
				break;
			}

            try {
                $key = self::readKey($str, $offset);
            } catch (\Exception $e) {
            }
            try {
                $value = self::readValue($str, $offset, $type);
            } catch (\Exception $e) {
            }

            $tag = NBT::createTag($type);
			if($tag instanceof NamedTag){
				$tag->setName($key);
				$tag->setValue($value);
				$data[$key] = $tag;
			}
		}

		return $data;
	}

	/**
	 * @param string $data
	 * @param int    $offset
	 * @param int|null $type
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private static function readValue(string $data, int &$offset, &$type = null){
		$value = "";
		$type = null;
		$inQuotes = false;

		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data[$offset];

			if(!$inQuotes and ($c === " " or $c === "\r" or $c === "\n" or $c === "\t" or $c === "," or $c === "}" or $c === "]")){
				if($c === "," or $c === "}" or $c === "]"){
					break;
				}
			}elseif($c === '"'){
				$inQuotes = !$inQuotes;
				if($type === null){
					$type = NBT::TAG_String;
				}elseif($inQuotes){
                    throw new \Exception("Синтаксическая ошибка: неверная кавычка по смещению $offset");
				}
			}elseif($c === "\\"){
				$value .= $data[$offset + 1] ?? "";
				++$offset;
			}elseif($c === "{" and !$inQuotes){
				if($value !== ""){
                    throw new \Exception("Синтаксическая ошибка: неверный составной старт со смещением $offset");
				}
				++$offset;
				$value = self::parseCompound($data, $offset);
				$type = NBT::TAG_Compound;
				break;
			}elseif($c === "[" and !$inQuotes){
				if($value !== ""){
                    throw new \Exception("Синтаксическая ошибка: неверный список начинается со смещения $offset");
				}
				++$offset;
				$value = self::parseList($data, $offset);
				$type = NBT::TAG_List;
				break;
			}else{
				$value .= $c;
			}
		}

		if($value === ""){
            throw new \Exception("Синтаксическая ошибка: недопустимое пустое значение по смещению $offset");
		}

		if($type === null and strlen($value) > 0){
			$value = trim($value);
			$last = strtolower(substr($value, -1));
			$part = substr($value, 0, -1);

			if($last !== "b" and $last !== "s" and $last !== "l" and $last !== "f" and $last !== "d"){
				$part = $value;
				$last = null;
			}

			if($last !== "f" and $last !== "d" and ((string) ((int) $part)) === $part){
				if($last === "b"){
					$type = NBT::TAG_Byte;
				}elseif($last === "s"){
					$type = NBT::TAG_Short;
				}elseif($last === "l"){
					$type = NBT::TAG_Long;
				}else{
					$type = NBT::TAG_Int;
				}
				$value = (int) $part;
			}elseif(is_numeric($part)){
				if($last === "f" or $last === "d" or strpos($part, ".") !== false){
					if($last === "f"){
						$type = NBT::TAG_Float;
					}elseif($last === "d"){
						$type = NBT::TAG_Double;
					}else{
						$type = NBT::TAG_Float;
					}
					$value = (float) $part;
				}else{
					if($last === "l"){
						$type = NBT::TAG_Long;
					}else{
						$type = NBT::TAG_Int;
					}

					$value = $part;
				}
			}else{
				$type = NBT::TAG_String;
			}
		}

		return $value;
	}

	/**
	 * @param string $data
	 * @param int $offset
	 *
	 * @return string
	 * @throws \Exception
	 */
	private static function readKey(string $data, int &$offset){
		$key = "";

		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data[$offset];

			if($c === ":"){
				++$offset;
				break;
			}elseif($c !== " " and $c !== "\r" and $c !== "\n" and $c !== "\t" and $c !== "\""){
				$key .= $c;
			}
		}

		if($key === ""){
            throw new \Exception("Синтаксическая ошибка: неверный пустой ключ по смещению $offset");
		}

		return $key;
	}
}