<?php

class Pixel
{
	protected int $red;
	protected int $green;
	protected int $blue;

	protected ColorRGB $color;

	public function __construct(int $red, int $green, int $blue)
	{
		$this->red = $red;
		$this->green = $green;
		$this->blue = $blue;

		$this->color = self::determineColor(
			$this->red,
			$this->green,
			$this->blue
		);
	}

	public function getRGB(): array
	{
		return [
			$this->red,
			$this->green,
			$this->blue
		];
	}

	public function getColor(): ColorRGB
	{
		return $this->color;
	}

	public static function determineColor(int $red, int $green, int $blue): ColorRGB
	{
		$FACTOR = 1.1;

		if (
			$red > $green * $FACTOR
			&& $red > $blue * $FACTOR
		) {
			return ColorRGB::Red;
		}

		if (
			$green > $red * $FACTOR
			&& $green > $blue * $FACTOR
		) {
			return ColorRGB::Green;
		}

		if (
			$blue > $red * $FACTOR
			&& $blue > $green * $FACTOR
		) {
			return ColorRGB::Blue;
		}

		return ColorRGB::Undefined;
	}

	public function getColorProbabilities(): array
	{
		$probabilities = [
			ColorRGB::Red->name => 0,
			ColorRGB::Green->name => 0,
			ColorRGB::Blue->name => 0,
			ColorRGB::Undefined->name => 0
		];

		$probabilities[ $this->color->name ] = 1;

		return $probabilities;
	}

	public function __toString(): string
	{
		return "({$this->red}, {$this->green}, {$this->blue}) = {$this->color->name}";
	}
}
