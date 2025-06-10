<?php
/**
 * Класс для представления пикселя с RGB значениями
 *
 * Инкапсулирует RGB значения пикселя (0-255) и предоставляет
 * методы для нормализации данных и определения цвета. Является основным
 * типом данных для входа в нейронную сеть.
 */
readonly class Pixel
{
	public function __construct(
		public int $red,
		public int $green,
		public int $blue
	) {
		if ($red < 0 || $red > 255 ||
			$green < 0 || $green > 255 ||
			$blue < 0 || $blue > 255) {
			throw new InvalidArgumentException('RGB values must be between 0 and 255');
		}
	}

	public function getNormalizedRGB(): array
	{
		return [
			$this->red / 255.0,
			$this->green / 255.0,
			$this->blue / 255.0,
		];
	}

	public function getColor(): Color
	{
		return Color::fromRGB($this->red, $this->green, $this->blue);
	}

	public function __toString(): string
	{
		return "RGB({$this->red}, {$this->green}, {$this->blue})";
	}
}
