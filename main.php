<?php

//require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/ColorRGB.php';
require_once __DIR__ . '/Pixel.php';
require_once __DIR__ . '/PixelFactory.php';
require_once __DIR__ . '/Sensor.php';

function trainSensor(Sensor $Sensor, int $iterations = 100): void
{
	for ($i = 0; $i < $iterations; $i++) {
		$Pixel = PixelFactory::getRandom();
		$Sensor->train(
			$Pixel->getRelativeChannelValues(),
			$Pixel->getColorProbabilities()
		);
	}
}

// тестирование обученной сети
function testSensor(Sensor $Sensor, int $iterations = 100, bool $debug = false): void
{
	$passed = 0;
	for ($i = 0; $i < $iterations; $i++) {
		$Pixel = PixelFactory::getRandom();
		$Sensor->putData( $Pixel->getRelativeChannelValues() );
		//$prediction = $Sensor->getPrediction();
		$realValue = $Pixel->getColor()->name;

		//if ($prediction[$realValue] > Sensor::PREDICTION_THRESHOLD_TRUE) {
		//	$passed++;
		//}
		if ($Sensor->is($realValue)) {
			$passed++;
		}

		if ($debug) {
			echo 'Real Value: ', $realValue, PHP_EOL;
			var_dump($prediction);
		}
	}

	echo "PASSED: {$passed}/{$iterations}", PHP_EOL;
}

$Sensor = new Sensor(
	Pixel::CHANNELS_NUM,
	[
		ColorRGB::Red->name,
		ColorRGB::Green->name,
		ColorRGB::Blue->name,
		ColorRGB::Undefined->name
	]
);
trainSensor($Sensor, 1000);
var_dump($Sensor->dumpMemory());
echo '######';
testSensor($Sensor, 1);
