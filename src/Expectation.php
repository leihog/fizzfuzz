<?php

namespace FizzFuzz;

use Assert\Assertion;
use Assert\AssertionFailedException;

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
                $message = sprintf($message, $this->value, $expected);
                Assertion::same($this->value, $expected, $message);
                break;
            case 1:
                // Test pattern is valid
                if (@preg_match($this->value, '') === false) {
                    Assertion::same(1, 2, sprintf('!!! FIZZFUZZ WARNING !!! `%s` is an invalid regular expression, this test was not executed', $this->value));
                } else {
                    $message = sprintf('Expected `%s` to match regex `%s`, but it didn\'t`', $expected, $this->value);
                    Assertion::regex($expected, $this->value, $message);
                }
                break;
            case 2:
                $message = sprintf($message, $this->value, gettype($expected));
                Assertion::same($this->value, gettype($expected), $message);
                break;
        }
    }
}
