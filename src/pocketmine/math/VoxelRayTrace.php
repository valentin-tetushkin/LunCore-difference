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

namespace pocketmine\math;

use function floor;
use const INF;

abstract class VoxelRayTrace{

	/**
     * Выполняет трассировку луча из начальной позиции в заданном направлении на расстоянии $maxDistance. Этот
     * возвращает генератор, который дает Vector3, содержащие координаты вокселей, через которые он проходит.
     *
	 * @return \Generator|Vector3[]
	 * @phpstan-return \Generator<int, Vector3, void, void>
	 */
	public static function inDirection(Vector3 $start, Vector3 $directionVector, float $maxDistance) : \Generator{
		return self::betweenPoints($start, $start->add($directionVector->multiply($maxDistance)));
	}

    /**
     * Выполняет трассировку луча между начальной и конечной координатами. Это возвращает генератор, который дает Vector3s
     * содержащий координаты вокселей, через которые он проходит.
     *
     * Это реализация алгоритма, описанного по ссылке ниже.
     * @ссылка http://www.cse.yorku.ca/~amana/research/grid.pdf
     *
     * @return \Генератор|Вектор3[]
     * @phpstan-return \Generator<int, Vector3, void, void>
     */
	public static function betweenPoints(Vector3 $start, Vector3 $end) : \Generator{
		$currentBlock = $start->floor();

		$directionVector = $end->subtract($start)->normalize();
		if($directionVector->lengthSquared() <= 0){
			throw new \InvalidArgumentException("Начальная и конечная точки совпадают, что дает нулевой вектор направления.");
		}

		$radius = $start->distance($end);

		$stepX = $directionVector->x <=> 0;
		$stepY = $directionVector->y <=> 0;
		$stepZ = $directionVector->z <=> 0;

       //Инициализировать переменные накопления шагов в зависимости от того, насколько далеко в текущем блоке находится начальная позиция. Если
        //начальная позиция находится в углу блока, они будут равны нулю.
		$tMaxX = self::rayTraceDistanceToBoundary($start->x, $directionVector->x);
		$tMaxY = self::rayTraceDistanceToBoundary($start->y, $directionVector->y);
		$tMaxZ = self::rayTraceDistanceToBoundary($start->z, $directionVector->z);

		//The change in t on each axis when taking a step on that axis (always positive).
		$tDeltaX = $directionVector->x == 0 ? 0 : $stepX / $directionVector->x;
		$tDeltaY = $directionVector->y == 0 ? 0 : $stepY / $directionVector->y;
		$tDeltaZ = $directionVector->z == 0 ? 0 : $stepZ / $directionVector->z;

		while(true){
			yield $currentBlock;

            // tMaxX хранит значение t, при котором мы пересекаем границу куба вдоль
            // ось X и аналогично для Y и Z. Следовательно, выбираем наименьшее значение tMax
            // выбирает ближайшую границу куба.
			if($tMaxX < $tMaxY and $tMaxX < $tMaxZ){
				if($tMaxX > $radius){
					break;
				}
				$currentBlock->x += $stepX;
				$tMaxX += $tDeltaX;
			}elseif($tMaxY < $tMaxZ){
				if($tMaxY > $radius){
					break;
				}
				$currentBlock->y += $stepY;
				$tMaxY += $tDeltaY;
			}else{
				if($tMaxZ > $radius){
					break;
				}
				$currentBlock->z += $stepZ;
				$tMaxZ += $tDeltaZ;
			}
		}
	}

    /**
     * Возвращает расстояние, которое необходимо пройти по оси от начальной точки с компонентой вектора направления до
     * пересечь границу блока.
     *
     * Например, учитывая координату X внутри блока и компонент X вектора направления, будет возвращено расстояние
     * пройдено этим компонентом направления, чтобы достичь блока с другой координатой X.
     *
     * Найдите наименьшее положительное t такое, что s+t*ds является целым числом.
     *
     * @param float $s Начальная координата
     * @param float $ds Компонент вектора направления соответствующей оси
     *
     * @return float Расстояние по трассе луча, которое необходимо пройти, чтобы пересечь границу.
     */
	private static function rayTraceDistanceToBoundary(float $s, float $ds) : float{
		if($ds == 0){
			return INF;
		}

		if($ds < 0){
			$s = -$s;
			$ds = -$ds;

			if(floor($s) == $s){ // точно по координате, немедленно покинет координату, двигаясь в отрицательном направлении
				return 0;
			}
		}

        // проблема теперь s+t*ds = 1
		return (1 - ($s - floor($s))) / $ds;
	}
}