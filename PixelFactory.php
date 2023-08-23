<?php

class PixelFactory
{
	public static function getRGBChannelValue(): int
	{
		return mt_rand(0, 255);
	}

	public static function getRandom(): Pixel
	{
		return new Pixel(
			self::getRGBChannelValue(),
			self::getRGBChannelValue(),
			self::getRGBChannelValue()
		);
	}
}
