<?php
/**
 * Перечисление основных цветов RGB
 *
 * Определяет 8 основных цветов и один неопределенный цвет.
 * Предоставляет методы для получения RGB значений и определения цвета по RGB.
 * Используется как эталонная система для обучения нейронной сети.
 */
enum Color
{
	case Red;
	case Green;
	case Blue;
	case Yellow;
	case Cyan;
	case Purple;
	case Black;
	case White;
	case Undefined;

	public static function getPalette(): array
	{
		return [
			'Red' => self::Red,
			'Green' => self::Green,
			'Blue' => self::Blue,
			'Yellow' => self::Yellow,
			'Cyan' => self::Cyan,
			'Purple' => self::Purple,
			'Black' => self::Black,
			'White' => self::White,
		];
	}

	public function getRGBValues(): array
	{
		return match($this) {
			self::Red => [255, 0, 0],
			self::Green => [0, 255, 0],
			self::Blue => [0, 0, 255],
			self::Yellow => [255, 255, 0],
			self::Cyan => [0, 255, 255],
			self::Purple => [255, 0, 255],
			self::Black => [0, 0, 0],
			self::White => [255, 255, 255],
			self::Undefined => [128, 128, 128],
		};
	}

	public static function fromRGB(int $r, int $g, int $b): self
	{
		if ($r > 200 && $g < 100 && $b < 100) return self::Red;
		if ($r < 100 && $g > 200 && $b < 100) return self::Green;
		if ($r < 100 && $g < 100 && $b > 200) return self::Blue;
		if ($r > 200 && $g > 200 && $b < 100) return self::Yellow;
		if ($r < 100 && $g > 200 && $b > 200) return self::Cyan;
		if ($r > 200 && $g < 100 && $b > 200) return self::Purple;
		if ($r < 50 && $g < 50 && $b < 50) return self::Black;
		if ($r > 200 && $g > 200 && $b > 200) return self::White;
		return self::Undefined;
	}
}
