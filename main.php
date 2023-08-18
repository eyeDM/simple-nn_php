<?php

require_once __DIR__ . '/ColorRGB.php';
require_once __DIR__ . '/Pixel.php';
require_once __DIR__ . '/DataGenerator.php';
require_once __DIR__ . '/Sensor.php';
require_once __DIR__ . '/Eye.php';

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
function testSensor(Sensor $Sensor, int $iterations = 10): void
{
	for ($i = 0; $i < $iterations; $i++) {
		$Pixel = DataGenerator::getPixel();
		$Sensor->putData( $Pixel->getRGB() );

		$expected = $Pixel->getColor()->name;
		$actual = $Sensor->getResult();
		if ($expected === $actual) {
			echo 'passed', PHP_EOL;
		} else {
			echo "expected: {$expected}, actual: {$actual}", PHP_EOL;
		}
	}
}

//$Pixel = DataGenerator::getPixel();
//echo $Pixel;

$Sensor = new Sensor(
	3,
	[
		ColorRGB::Red->name,
		ColorRGB::Green->name,
		ColorRGB::Blue->name,
		ColorRGB::Undefined->name
	]
);
trainSensor($Sensor);
//testSensor($Sensor);
