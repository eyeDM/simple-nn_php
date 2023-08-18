<?php

class Sensor
{
	protected const DEFAULT_ASSOCIATION = 0.1;
	protected const CORRECTION_STEP = 0.1;

	protected int $channelsNum;

	/**
	 * Насколько сильно сигнал по некоторому каналу
	 * вызывает "ассоциацию" с некоторым результатом.
	 *
	 * [$channel => [string $possibleResult => float $association]]
	 * 0 <= $association <= 1
	 *
	 * @var SplFixedArray
	 */
	protected SplFixedArray $memory;

	protected SplFixedArray $data;

	protected SplFixedArray $possibleResults;

	protected $valueNormalizer;

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

	protected function increaseAssociation(int $channel, string $possibleResult): void
	{
		$association = $this->getAssociation($channel, $possibleResult);
		$association *= (1 + self::CORRECTION_STEP);
		if ($association > 1) {
			$association = 1;
		}
		$this->setAssociation($channel, $possibleResult, $association);
	}

	protected function decreaseAssociation(int $channel, string $possibleResult): void
	{
		$association = $this->getAssociation($channel, $possibleResult);
		$association *= (1 - self::CORRECTION_STEP);
		$this->setAssociation($channel, $possibleResult, $association);
	}

	protected function randomChangeAssociation(int $channel, string $possibleResult): void
	{
		// TODO
	}

	public function __construct(int $channelsNum, array $possibleResults, callable $valueNormalizer)
	{
		$this->channelsNum = $channelsNum;
		$this->initMemory($possibleResults);
		$this->data = new SplFixedArray($this->channelsNum);
		$this->possibleResults = SplFixedArray::fromArray($possibleResults);
		$this->valueNormalizer = $valueNormalizer;
	}

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

	public function dumpMemory(): SplFixedArray
	{
		return $this->memory;
	}

	public function putData(array $values): void
	{
		for ($channel = 0; $channel < $this->channelsNum; $channel++) {
			$this->data[ $channel ] = isset($values[ $channel ])
				? $this->normalizeValue($values[ $channel ])
				: 0;
		}
	}

	/**
	 * Насколько уровень сигнала в каждом из каналов
	 * напоминает _такой_ результат?
	 *
	 * @param  string  $possibleResult
	 * @return float [0..1]
	 */
	protected function getAssociationLevel(string $possibleResult): float
	{
		$result = 0;
		foreach ($this->data as $channel => $value) {
			$result += $this->getAssociation($channel, $possibleResult) * $value;
		}

		// просто среднее значение? хм...
		return ($result / $this->channelsNum);
	}

	/**
	 * Вероятности того, что бы "увиден" каждый из возможных результатов.
	 *
	 * @return array
	 */
	public function getPrediction(): array
	{
		$result = [];

		foreach ($this->possibleResults as $possibleResult) {
			$result[ $possibleResult ] = $this->getAssociationLevel($possibleResult);
		}

		return $result;
	}

	/**
	 * Сенсор выдаёт "предсказание", оно сравнивается с "реальностью";
	 * в зависимости от того, насколько "предсказание" похоже на "реальность",
	 * корректируем память.
	 *
	 * @param  array  $expectedResult  [$possibleResult => $realProbability]
	 * @return void
	 */
	protected function doCorrection(array $expectedResult): void
	{
		foreach ($this->getPrediction() as $possibleResult => $association) {
			// надо сравнить $association с $expectedResult[ $possibleResult ];
			// если "вклад" ячейки памяти был положительным - усиливаем ассоциацию,
			// иначе - уменьшаем
			if (
				($expectedResult[ $possibleResult ] && $association > PREDICTION_THRESHOLD_TRUE)
				|| (!$expectedResult[ $possibleResult ] && $association < PREDICTION_THRESHOLD_FALSE)
			) {
				// верный ответ, усиливаем ассоциацию
				foreach ($this->data as $channel => $value) {
					if ($value > PREDICTION_THRESHOLD_TRUE) {
						$this->increaseAssociation($channel, $possibleResult);
					} else {
						$this->randomChangeAssociation($channel, $possibleResult);
					}
				}
			} else {
				foreach ($this->data as $channel => $value) {
					if ($value > PREDICTION_THRESHOLD_TRUE) {
						$this->decreaseAssociation($channel, $possibleResult);
					} else {
						$this->randomChangeAssociation($channel, $possibleResult);
					}
				}
			}
		}
	}

	public function train(array $values, array $expectedResult): void
	{
		$this->putData($values);
		$this->doCorrection($expectedResult);
	}
}
