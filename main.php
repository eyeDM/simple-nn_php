<?php

define('PREDICTION_THRESHOLD_FALSE', 0.25);
define('PREDICTION_THRESHOLD_TRUE', 0.75);

require_once __DIR__ . '/ColorRGB.php';
require_once __DIR__ . '/Pixel.php';
require_once __DIR__ . '/DataGenerator.php';
require_once __DIR__ . '/Sensor.php';

function trainSensor(Sensor $Sensor, int $iterations = 10): void
{
	for ($i = 0; $i < $iterations; $i++) {
		$Pixel = DataGenerator::getPixel();
		$Sensor->train(
			$Pixel->getRGB(),
			$Pixel->getColorProbabilities()
		);
	}
}

// тестирование обученной сети
function testSensor(Sensor $Sensor, int $iterations = 10, bool $debug = false): void
{
	$passed = 0;
	for ($i = 0; $i < $iterations; $i++) {
		$Pixel = DataGenerator::getPixel();
		$Sensor->putData( $Pixel->getRGB() );

		$realValue = $Pixel->getColor()->name;
		$prediction = $Sensor->getPrediction();

		if ($prediction[$realValue] > PREDICTION_THRESHOLD_TRUE) {
			$passed++;
		}

		if ($debug) {
			echo 'Real Value: ', $realValue, PHP_EOL;
			var_dump($prediction);
		}
	}

	echo "PASSED: {$passed}/{$iterations}", PHP_EOL;
}

$valueNormalizer = static function (float $value): float
{
	//return 1 / (1 + M_E ** -$value);
	return $value / 255;
};

$Sensor = new Sensor(
	3,
	[
		ColorRGB::Red->name,
		ColorRGB::Green->name,
		ColorRGB::Blue->name,
		ColorRGB::Undefined->name
	],
	$valueNormalizer
);
trainSensor($Sensor, 30);
var_dump($Sensor->dumpMemory());
//testSensor($Sensor);
