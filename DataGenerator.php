<?php

class DataGenerator
{
	public static function getChannel(): int
	{
		return rand(0, 255);
	}

	public static function getPixel(): Pixel
	{
		return new Pixel(
			self::getChannel(),
			self::getChannel(),
			self::getChannel()
		);
	}
}
