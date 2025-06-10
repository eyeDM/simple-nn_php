<?php
/**
 * Система распознавания цветов на основе нейронной сети
 *
 * Высокоуровневый интерфейс для обучения и использования
 * нейронной сети для классификации цветов. Управляет:
 * - генерацией обучающих данных с различными вариациями цветов;
 * - процессом обучения с отслеживанием прогресса;
 * - предсказанием цветов для новых пикселей;
 * - оценкой точности на тестовых данных.
 *
 *  АРХИТЕКТУРА СЕТИ:
 *  - Входной слой: 3 нейрона (RGB каналы, нормализованные в 0.0-1.0).
 *  - Скрытый слой: 16 нейронов.
 *    * Достаточно для изучения нелинейных комбинаций RGB
 *    * Больше чем входной слой для расширения пространства признаков
 *    * Не слишком много, чтобы избежать переобучения
 *  - Выходной слой: 8 нейронов (вероятность каждого из 8 цветов(softmax)).
 *
 *  Такая архитектура обеспечивает:
 *  1. Достаточную сложность для изучения цветовых паттернов.
 *  2. Быструю сходимость (мало параметров = быстрое обучение).
 *  3. Устойчивость к переобучению.
 *  4. Хорошую обобщающую способность.
 *
 * СТРАТЕГИЯ ОБУЧАЮЩИХ ДАННЫХ:
 * - 30% чистых цветов (без шума) - для изучения эталонов;
 * - 40% слегка измененных цветов (±30 RGB) - для базовой устойчивости;
 * - 30% сильно измененных цветов (±60 RGB) - для робастности.
 * Такое распределение обеспечивает хорошее обобщение на реальные данные.
 */
class ColorRecognitionSystem
{
	private array $colorNames;
	private array $colors;
	private NeuralNetwork $network;
	private array $trainingHistory;

	public function __construct(array $colorPalette)
	{
		$this->colorNames = array_keys($colorPalette);
		$this->colors = array_values($colorPalette);

		$this->network = new NeuralNetwork([3, 16, count($this->colors)], 0.5);
		$this->trainingHistory = [];
	}

	/**
	 * @param mixed $r
	 * @param int   $noiseR
	 * @param mixed $g
	 * @param int   $noiseG
	 * @param mixed $b
	 * @param int   $noiseB
	 * @return float[]
	 */
	private function generateInput(
		mixed $r, int $noiseR,
		mixed $g, int $noiseG,
		mixed $b, int $noiseB
	): array {
		$newR = max(0, min(255, $r + $noiseR));
		$newG = max(0, min(255, $g + $noiseG));
		$newB = max(0, min(255, $b + $noiseB));

		return (new Pixel($newR, $newG, $newB))->getNormalizedRGB();
	}

	public function generateTrainingData(int $samplesPerColor = 300): array
	{
		$trainingData = [];

		foreach ($this->colors as $colorIndex => $color) {
			[$r, $g, $b] = $color->getRGBValues();

			for ($i = 0; $i < $samplesPerColor; $i++) {
				// Create variations with different noise levels
				if ($i < $samplesPerColor * 0.3) {
					// 30% pure colors
					$noiseR = $noiseG = $noiseB = 0;
				} elseif ($i < $samplesPerColor * 0.7) {
					// 40% slight variations
					$noiseR = random_int(-30, 30);
					$noiseG = random_int(-30, 30);
					$noiseB = random_int(-30, 30);
				} else {
					// 30% more variations
					$noiseR = random_int(-60, 60);
					$noiseG = random_int(-60, 60);
					$noiseB = random_int(-60, 60);
				}

				$inputs = $this->generateInput($r, $noiseR, $g, $noiseG, $b, $noiseB);

				// One-hot encoding
				$expected = array_fill(0, 8, 0.0);
				$expected[$colorIndex] = 1.0;

				$trainingData[] = [$inputs, $expected, $colorIndex];
			}
		}

		shuffle($trainingData);
		return $trainingData;
	}

	public function train(int $epochs = 200, int $samplesPerColor = 300): void
	{
		echo "Generating training data ({$samplesPerColor} samples per color)...\n";
		$trainingData = $this->generateTrainingData($samplesPerColor);

		echo "Training neural network for {$epochs} epochs...\n";

		for ($epoch = 0; $epoch < $epochs; $epoch++) {
			$totalLoss = 0;
			$correct = 0;

			// Shuffle training data each epoch
			shuffle($trainingData);

			foreach ($trainingData as [$inputs, $expected, $expectedIndex]) {
				$loss = $this->network->train($inputs, $expected);
				$totalLoss += $loss;

				$predictedIndex = $this->network->getBestPrediction($inputs);
				if ($predictedIndex === $expectedIndex) {
					$correct++;
				}
			}

			$accuracy = $correct / count($trainingData);
			$avgLoss = $totalLoss / count($trainingData);

			$this->trainingHistory[] = [
				'epoch' => $epoch + 1,
				'loss' => $avgLoss,
				'accuracy' => $accuracy,
			];

			if (($epoch + 1) % 20 === 0 || $epoch === 0 || $epoch === $epochs - 1) {
				printf("Epoch %d: Loss = %.4f, Accuracy = %.2f%%\n",
					   $epoch + 1, $avgLoss, $accuracy * 100);
			}
		}
	}

	public function predict(Pixel $pixel): array
	{
		$inputs = $pixel->getNormalizedRGB();
		$output = $this->network->predict($inputs);

		$result = [];
		for ($i = 0, $iMax = count($this->colorNames); $i < $iMax; $i++) {
			$result[$this->colorNames[$i]] = $output[$i];
		}

		return $result;
	}

	public function getBestPrediction(Pixel $pixel): string
	{
		$inputs = $pixel->getNormalizedRGB();
		$bestIndex = $this->network->getBestPrediction($inputs);
		return $this->colorNames[$bestIndex];
	}

	public function getTrainingHistory(): array
	{
		return $this->trainingHistory;
	}

	public function testAccuracy(int $testSamples = 100): float
	{
		echo "\nGenerating test data...\n";
		$testData = [];

		foreach ($this->colors as $colorIndex => $color) {
			[$r, $g, $b] = $color->getRGBValues();

			for ($i = 0; $i < $testSamples; $i++) {
				$noiseR = random_int(-40, 40);
				$noiseG = random_int(-40, 40);
				$noiseB = random_int(-40, 40);

				$inputs = $this->generateInput($r, $noiseR, $g, $noiseG, $b, $noiseB);

				$testData[] = [$inputs, $colorIndex];
			}
		}

		return $this->network->getAccuracy($testData);
	}
}
