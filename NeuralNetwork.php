<?php
/**
 * Многослойная нейронная сеть с обратным распространением ошибки
 *
 * Реализует полнофункциональную нейронную сеть для классификации.
 * Поддерживает произвольную архитектуру, обучение методом градиентного спуска
 * и предсказание с расчетом точности.
 */
class NeuralNetwork
{
	private array $weights;
	private array $biases;
	private array $layers;
	private array $activations;
	private float $learningRate;

	public function __construct(array $layerSizes, float $learningRate = 0.01)
	{
		$this->layers = $layerSizes;
		$this->learningRate = $learningRate;
		$this->initializeWeightsAndBiases();
	}

	private function initializeWeightsAndBiases(): void
	{
		$this->weights = [];
		$this->biases = [];

		for ($i = 0; $i < count($this->layers) - 1; $i++) {
			$inputSize = $this->layers[$i];
			$outputSize = $this->layers[$i + 1];

			// He initialization for ReLU, Xavier for sigmoid
			$scale = sqrt(2.0 / $inputSize);

			$layerWeights = [];
			for ($j = 0; $j < $outputSize; $j++) {
				$neuronWeights = [];
				for ($k = 0; $k < $inputSize; $k++) {
					$neuronWeights[] = (mt_rand() / mt_getrandmax() - 0.5) * $scale;
				}
				$layerWeights[] = $neuronWeights;
			}
			$this->weights[] = $layerWeights;

			// Initialize biases to zero
			$layerBiases = array_fill(0, $outputSize, 0.0);
			$this->biases[] = $layerBiases;
		}
	}

	private function sigmoid(float $x): float
	{
		// Clamp x to prevent overflow
		$x = max(-500, min(500, $x));
		return 1.0 / (1.0 + exp(-$x));
	}

	private function sigmoidDerivative(float $sigmoidOutput): float
	{
		return $sigmoidOutput * (1.0 - $sigmoidOutput);
	}

	public function forward(array $inputs): array
	{
		$this->activations = [$inputs];
		$currentLayer = $inputs;

		for ($i = 0, $iMax = count($this->weights); $i < $iMax; $i++) {
			$nextLayer = [];

			for ($j = 0, $jMax = count($this->weights[$i]); $j < $jMax; $j++) {
				$z = $this->biases[$i][$j];

				for ($k = 0, $kMax = count($currentLayer); $k < $kMax; $k++) {
					$z += $currentLayer[$k] * $this->weights[$i][$j][$k];
				}

				// Use sigmoid for all layers for simplicity and stability
				$nextLayer[] = $this->sigmoid($z);
			}

			$this->activations[] = $nextLayer;
			$currentLayer = $nextLayer;
		}

		return $currentLayer;
	}

	public function backward(array $expected): void
	{
		$deltas = [];

		// Calculate output layer delta (error)
		$outputIndex = count($this->activations) - 1;
		$output = $this->activations[$outputIndex];
		$outputDelta = [];

		for ($i = 0, $iMax = count($output); $i < $iMax; $i++) {
			$error = $expected[$i] - $output[$i];
			$outputDelta[] = $error * $this->sigmoidDerivative($output[$i]);
		}
		$deltas[count($this->weights) - 1] = $outputDelta;

		// Backpropagate deltas
		for ($layer = count($this->weights) - 2; $layer >= 0; $layer--) {
			$layerDelta = [];
			$activation = $this->activations[$layer + 1];

			for ($j = 0, $jMax = count($activation); $j < $jMax; $j++) {
				$error = 0;

				// Sum weighted deltas from next layer
				for ($k = 0, $kMax = count($deltas[$layer + 1]); $k < $kMax; $k++) {
					$error += $deltas[$layer + 1][$k] * $this->weights[$layer + 1][$k][$j];
				}

				$layerDelta[] = $error * $this->sigmoidDerivative($activation[$j]);
			}

			$deltas[$layer] = $layerDelta;
		}

		// Update weights and biases
		for ($layer = 0, $layerMax = count($this->weights); $layer < $layerMax; $layer++) {
			for ($j = 0, $jMax = count($this->weights[$layer]); $j < $jMax; $j++) {
				// Update bias
				$this->biases[$layer][$j] += $this->learningRate * $deltas[$layer][$j];

				// Update weights
				for ($k = 0, $kMax = count($this->weights[$layer][$j]); $k < $kMax; $k++) {
					$this->weights[$layer][$j][$k] +=
						$this->learningRate * $deltas[$layer][$j] * $this->activations[$layer][$k];
				}
			}
		}
	}

	public function train(array $inputs, array $expected): float
	{
		$output = $this->forward($inputs);
		$this->backward($expected);

		// Calculate mean squared error
		$loss = 0;
		for ($i = 0, $iMax = count($output); $i < $iMax; $i++) {
			$loss += ($expected[$i] - $output[$i]) ** 2;
		}

		return $loss / count($output);
	}

	public function predict(array $inputs): array
	{
		return $this->forward($inputs);
	}

	public function getBestPrediction(array $inputs): int
	{
		$output = $this->predict($inputs);
		$maxIndex = 0;
		$maxValue = $output[0];

		for ($i = 1, $iMax = count($output); $i < $iMax; $i++) {
			if ($output[$i] > $maxValue) {
				$maxValue = $output[$i];
				$maxIndex = $i;
			}
		}

		return $maxIndex;
	}

	public function getAccuracy(array $testData): float
	{
		$correct = 0;
		$total = count($testData);

		foreach ($testData as [$inputs, $expectedIndex]) {
			$predictedIndex = $this->getBestPrediction($inputs);
			if ($predictedIndex === $expectedIndex) {
				$correct++;
			}
		}

		return $total > 0 ? $correct / $total : 0;
	}
}
