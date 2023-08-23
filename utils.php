<?php

function trueOrFalse(): bool
{
	return random_int(0, 1);
}

function sigmoid(float $value): float
{
	return 1 / (1 + M_E ** -$value);
}
