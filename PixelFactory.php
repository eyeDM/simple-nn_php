<?php
/**
 * Фабрика для создания объектов {@link Pixel}
 * для упрощения генерации тестовых и обучающих данных
 *
 * Предоставляет статические методы для создания пикселей:
 * - создание случайных пикселей для тестирования;
 * - создание пикселей определенных цветов из enum {@link Color}.
 */
class PixelFactory
{
	private static function getRGBChannelValue(): int
	{
		return random_int(0, 255);
	}

	public static function getRandom(): Pixel
	{
		return new Pixel(
			self::getRGBChannelValue(),
			self::getRGBChannelValue(),
			self::getRGBChannelValue()
		);
	}

	public static function createColor(Color $color): Pixel
	{
		[$r, $g, $b] = $color->getRGBValues();
		return new Pixel($r, $g, $b);
	}
}
