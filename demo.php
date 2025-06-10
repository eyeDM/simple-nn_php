<?php

require_once __DIR__ . '/Color.php';
require_once __DIR__ . '/Pixel.php';
require_once __DIR__ . '/PixelFactory.php';
require_once __DIR__ . '/NeuralNetwork.php';
require_once __DIR__ . '/ColorRecognitionSystem.php';

/**
 * Демонстрация работы системы распознавания цветов:
 *
 * - обучение нейронной сети на синтетических данных;
 * - тестирование точности на независимых тестовых данных;
 * - отображение предсказаний для различных цветов;
 * - отображение статистики обучения и финальной точности.
 */
function run(): void
{
	echo "=== Neural Network Color Recognition ===\n\n";

	$system = new ColorRecognitionSystem(Color::getPalette());

	// 100 epochs, 400 samples
	$system->train(100, 400);

	$testAccuracy = $system->testAccuracy(50);
	echo "\nTest Accuracy: ", round($testAccuracy * 100, 1), "%\n";

	echo "\nTesting the trained system:\n",  str_repeat('=', 50), "\n";

	// Test with pure colors and some variations
	$testPixels = [
		PixelFactory::createColor(Color::Red),
		new Pixel(200, 50, 50), // Darker red
		PixelFactory::createColor(Color::Green),
		new Pixel(50, 200, 50), // Darker green
		PixelFactory::createColor(Color::Blue),
		new Pixel(50, 50, 200), // Darker blue
		PixelFactory::createColor(Color::Yellow),
		PixelFactory::createColor(Color::Cyan),
		PixelFactory::createColor(Color::Purple),
		PixelFactory::createColor(Color::Black),
		PixelFactory::createColor(Color::White),
		new Pixel(180, 180, 180), // Light gray
		PixelFactory::getRandom(),
	];

	foreach ($testPixels as $pixel) {
		echo "\nTesting pixel: {$pixel}\n";

		$predictions = $system->predict($pixel);
		$bestPrediction = $system->getBestPrediction($pixel);

		echo "Best prediction: {$bestPrediction}\n";
		echo "All predictions:\n";

		arsort($predictions);
		foreach ($predictions as $color => $probability) {
			$percentage = round($probability * 100, 1);
			echo "  {$color}: {$percentage}%\n";
		}
	}

	// Show final training statistics
	$history = $system->getTrainingHistory();
	$final = end($history);
	echo "\n", str_repeat('=', 50), "\n";
	echo 'Final Training Results:', "\n";
	echo 'Final Loss: ', round($final['loss'], 4), "\n";
	echo 'Final Training Accuracy: ', round($final['accuracy'] * 100, 1), "%\n";
	echo 'Test Accuracy: ', round($testAccuracy * 100, 1), "%\n";
}

run();
