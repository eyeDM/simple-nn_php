<?php

class Sensor
{
	protected const DEFAULT_ASSOCIATION = 0.1;

	protected int $channelsNum;

	/**
	 * [$channel => [string $possibleResult => float $association]]
	 * 0 <= $association <= 1
	 *
	 * @var SplFixedArray
	 */
	protected SplFixedArray $memory;

	protected SplFixedArray $data;

	protected SplFixedArray $possibleResults;

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

	public function __construct(int $channelsNum, array $possibleResults)
	{
		$this->channelsNum = $channelsNum;
		$this->initMemory($possibleResults);
		$this->data = new SplFixedArray($channelsNum);
		$this->possibleResults = SplFixedArray::fromArray($possibleResults);
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
		// TODO
		foreach ($this->getPrediction() as $possibleResult => $association) {
			// надо сравнить $association с $expectedResult[ $possibleResult ]
			foreach ($this->data as $channel => $value) {
				// если "вклад" ячейки памяти был положительным - усиливаем ассоциацию,
				// иначе - уменьшаем
				$this->memory[ $channel ][ $possibleResult ];
			}
		}
	}

	public function train(array $values, array $expectedResult): void
	{
		$this->putData($values);
		$this->doCorrection($expectedResult);
	}

	public function putData(array $values): void
	{
		$this->data = SplFixedArray::fromArray($values);
		// TODO: проверка длины массива
	}

	protected function getMemory(int $channel, string $possibleResult): float
	{
		return $this->memory[ $channel ][ $possibleResult ];
	}

	/**
	 * Насколько уровень сигнала в каждом из каналов
	 * напоминает _такой_ результат?
	 *
	 * @param  string  $possibleResult
	 * @return float [0..1]
	 */
	protected function getAssociation(string $possibleResult): float
	{
		// TODO: отображение в [0..1]
		$result = 0;
		foreach ($this->data as $channel => $value) {
			$result += $this->getMemory($channel, $possibleResult) * $value;
		}

		return ($result / $this->channelsNum);
	}

	public function getPrediction(): array
	{
		$result = [];

		foreach ($this->possibleResults as $possibleResult) {
			$result[ $possibleResult ] = $this->getAssociation($possibleResult);
		}

		return $result;
	}

	public function getResult(): string
	{
		// TODO
		return '';
	}
}
