<?php

class Sensor
{
	protected const DEFAULT_ASSOCIATION = 0.2;
	protected const CORRECTION_STEP = 0.2;

	protected int $channelsNum;

	/**
	 * Насколько сильно сигнал по некоторому каналу
	 * вызывает "ассоциацию" с некоторым результатом.
	 *
	 * [$channel => [string $possibleResult => float $association]]
	 * -1 <= $association <= 1
	 *
	 * @var SplFixedArray
	 */
	protected SplFixedArray $memory;

	protected SplFixedArray $data;

	protected SplFixedArray $possibleResults;

	protected $valueNormalizer;

	/**
	 * Отображение значение в интервал [0..1].
	 *
	 * @param  float  $value
	 * @return float
	 */
	protected function normalizeValue(float $value): float
	{
		return call_user_func($this->valueNormalizer, $value);
	}

	public function __construct(int $channelsNum, array $possibleResults, callable $valueNormalizer)
	{
		$this->channelsNum = $channelsNum;
		$this->initMemory($possibleResults);
		$this->data = new SplFixedArray($this->channelsNum);
		$this->possibleResults = SplFixedArray::fromArray($possibleResults);
		$this->valueNormalizer = $valueNormalizer;
	}

	public function putData(array $values): void
	{
		for ($channel = 0; $channel < $this->channelsNum; $channel++) {
			$this->data[ $channel ] = isset($values[ $channel ])
				? $this->normalizeValue($values[ $channel ])
				: 0;
		}
	}

	protected function initMemory(array $possibleResults): void
	{
		$defaultMemoryCell = [];
		foreach ($possibleResults as $possibleResult) {
			$defaultMemoryCell[ $possibleResult ] = self::DEFAULT_ASSOCIATION;
		}

		$this->memory = new SplFixedArray($this->channelsNum);
		for ($channel = 0; $channel < $this->channelsNum; $channel++) {
			$this->memory[ $channel ] = $defaultMemoryCell;
		}
	}

	protected function getAssociation(int $channel, string $possibleResult): float
	{
		return $this->memory[ $channel ][ $possibleResult ];
	}

	protected function setAssociation(int $channel, string $possibleResult, float $association): void
	{
		$memoryCell = $this->memory[ $channel ];
		$memoryCell[ $possibleResult ] = $association;
		$this->memory[ $channel ] = $memoryCell;
	}

	protected function increaseAssociation(int $channel, string $possibleResult, float $changeFactor): void
	{
		$association = $this->getAssociation($channel, $possibleResult);
		// $association += $association * $changeFactor + self::CORRECTION_STEP * $changeFactor
		$association += ($association + self::CORRECTION_STEP) * $changeFactor;
		if ($association > 1) {
			$association = 1;
		}
		$this->setAssociation($channel, $possibleResult, $association);
	}

	protected function decreaseAssociation(int $channel, string $possibleResult, float $changeFactor): void
	{
		$association = $this->getAssociation($channel, $possibleResult);
		// $association -= $association * $changeFactor + self::CORRECTION_STEP * $changeFactor
		$association -= ($association + self::CORRECTION_STEP) * $changeFactor;
		if ($association < -1) {
			$association = -1;
		}
		$this->setAssociation($channel, $possibleResult, $association);
	}

	/**
	 * 60% - малая мутация
	 * 30% - нет мутации
	 * 10% - существенная мутация
	 *
	 * @param  int     $channel
	 * @param  string  $possibleResult
	 * @return void
	 * @throws Exception
	 */
	protected function randomChangeAssociation(int $channel, string $possibleResult): void
	{
		$doIt = random_int(1, 100);

		if ($doIt > 70) {
			$this->increaseAssociation($channel, $possibleResult, lcg_value() / 4);
		} elseif ($doIt > 40) {
			$this->decreaseAssociation($channel, $possibleResult, lcg_value() / 4);
		} elseif ($doIt <= 5) {
			$this->increaseAssociation($channel, $possibleResult, lcg_value());
		} elseif ($doIt <= 10) {
			$this->decreaseAssociation($channel, $possibleResult, lcg_value());
		}
	}

	/**
	 * Сенсор выдаёт "предсказание", оно сравнивается с "реальностью";
	 * в зависимости от того, насколько "предсказание" похоже на "реальность",
	 * корректируем память.
	 *
	 * @param  array  $expectedResults  [$result => $realProbability]
	 * @return void
	 */
	protected function doCorrection(array $expectedResults): void
	{
		// TODO: может, надо не абсолютную силу сигнала учитывать, а относительную?

		foreach ($this->getPrediction() as $result => $probability) {
			if ($expectedResults[ $result ]) {
				// увеличиваем ассоциацию на каналах, получивших высокий уровень сигнала;
				// случайно меняет на остальных
				foreach ($this->data as $channel => $value) {
					if ($value > 0.5) {
						$this->increaseAssociation(
							$channel,
							$result,
							$probability * 4
						);
					} else {
						$this->randomChangeAssociation($channel, $result);
					}
				}
			} else {
				// уменьшаем ассоциацию на каналах, получивших низкий уровень сигнала;
				// случайно меняет на остальных
				foreach ($this->data as $channel => $value) {
					if ($value < 0.5) {
						$this->decreaseAssociation(
							$channel,
							$result,
							$probability
						);
					} else {
						$this->randomChangeAssociation($channel, $result);
					}
				}
			}
		}
	}

	public function dumpMemory(): SplFixedArray
	{
		return $this->memory;
	}

	/**
	 * Насколько уровень сигнала в каждом из каналов
	 * напоминает _такой_ результат?
	 *
	 * @param  string  $possibleResult
	 * @return float   [0..1]
	 */
	protected function getAssociationLevel(string $possibleResult): float
	{
		$result = 0;
		foreach ($this->data as $channel => $value) {
			$result += $this->getAssociation($channel, $possibleResult) * $value;
		}

		// просто среднее значение? хм...
		$average = $result / $this->channelsNum;

		if ($average > 1) {
			return 1;
		}
		if ($average < 0) {
			return 0;
		}

		return $average;
	}

	/**
	 * Вероятности того, что бы "увиден" каждый из возможных результатов.
	 *
	 * @return float[]
	 */
	public function getPrediction(): array
	{
		$result = [];

		foreach ($this->possibleResults as $possibleResult) {
			$result[ $possibleResult ] = $this->getAssociationLevel($possibleResult);
		}

		//var_dump($result);
		return $result;
	}

	public function train(array $values, array $expectedResults): void
	{
		//var_dump($values);
		$this->putData($values);
		$this->doCorrection($expectedResults);
		//var_dump($this->memory);
	}
}
