<?php

namespace FizzFuzz;

use Assert\Assertion;

class Expectation
{
    const STRATEGY_VALUE = 0;
    const STRATEGY_REGEX = 1;
    const STRATEGY_TYPE = 2;

    protected $type;
    protected $key;
    protected $value;
    protected $strategy = -1;

    public function __construct($type, $key)
    {
        $this->type = $type;
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValue($value)
    {
        $this->value = $value;
        $this->strategy = self::STRATEGY_VALUE;
        return $this;
    }

    public function setValueRegex($regex)
    {
        $this->value = $regex;
        $this->strategy = self::STRATEGY_REGEX;
        return $this;
    }

    public function setValueType($type)
    {
        $this->value = $type;
        $this->strategy = self::STRATEGY_TYPE;
        return $this;
    }

    public function evaluate($expected, $message)
    {
        switch ($this->strategy) {
            case 0:
                $message = sprintf($message, $this->value);
                Assertion::same($this->value, $expected, $message);
                break;
            case 1:
                Assertion::regex($expected, $this->value, $message);
                break;
            case 2:
                Assertion::same($this->value, gettype($expected), $message);
                break;
        }
    }
}
