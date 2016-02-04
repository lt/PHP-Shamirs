<?php declare(strict_types = 1);

namespace SSSS;

class Share
{
    private $number;
    private $value;

    function __construct(int $number, \GMP $value)
    {
        $this->number = $number;
        $this->value = $value;
    }

    function number(): int
    {
        return $this->number;
    }

    function value(): \GMP
    {
        return $this->value;
    }
}
