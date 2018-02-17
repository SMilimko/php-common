<?php

namespace Saritasa;

use Saritasa\Exceptions\InvalidEnumValueException;

/**
 * Enum implementation for PHP, alternative to \SplEnum.
 *
 * Uses reflection to get list of constants for a given enum subclass, but results are statically cached.
 *
 * @package App
 */
abstract class Enum implements \JsonSerializable
{
    /**
     * The constants' cache.
     *
     * @var array
     */
    private static $constants = [];

    /**
     * The name of an enum constant associated with the given enum instance.
     *
     * @var string
     */
    protected $constant = '';

    /**
     * Returns the class's constants.
     *
     * @return array
     */
    final public static function getConstants(): array
    {
        $class = static::class;
        if (isset(self::$constants[$class])) {
            return self::$constants[$class];
        }
        return self::$constants[$class] = (new \ReflectionClass($class))->getConstants();
    }

    /**
     * Returns the available constant names.
     *
     * @return array
     */
    final public static function getConstantNames(): array
    {
        return array_keys(self::getConstants());
    }

    /**
     * Checks if the given constant name is in the enum type.
     *
     * @param string $name
     * @return bool
     */
    public static function isValidConstantName($name): bool
    {
        return array_key_exists($name, self::getConstants());
    }

    /**
     * Checks if the given value is in the enum type.
     *
     * @param mixed $value
     * @param bool $strict Determines whether to search for identical elements.
     * @return bool
     */
    public static function isValidConstantValue($value, $strict = false): bool
    {
        return in_array($value, self::getConstants(), $strict);
    }

    /**
     * Validates the constant name.
     *
     * @param string $name The constant name.
     * @return void
     * @throws InvalidEnumValueException
     */
    public static function validate($name)
    {
        if (!static::isValidConstantName($name)) {
            throw new InvalidEnumValueException($name, array_keys(self::getConstants()));
        }
    }

    /**
     * Returns value by constant name.
     *
     * @param string $name The constant name.
     * @return mixed
     * @throws InvalidEnumValueException
     */
    public static function getConstantValue($name)
    {
        static::validate($name);
        return self::getConstants()[$name];
    }

    /**
     * Creates an enum instance that associated with the given enum constant name.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws InvalidEnumValueException
     * @throws \BadMethodCallException
     */
    final public static function __callStatic(string $name, array $arguments)
    {
        $value = static::getConstantValue($name);
        $value = is_array($value) ? $value : [$value];
        $class = static::class;
        $class = new $class(...$value);
        $class->constant = $name;
        if ($arguments) {
            $method = 'get' . ucfirst(reset($arguments));
            if (method_exists($class, $method)) {
                return $class->{$method}(...$arguments);
            }
            throw new \BadMethodCallException("Method $method does not exist.");
        }
        return $class;
    }

    /**
     * Returns the name of the constant that associated with the current enum instance.
     *
     * @return string
     */
    public function getConstantName(): string
    {
        return $this->constant;
    }

    /**
     * Converts the enum instance to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getConstantName();
    }

    /**
     * Returns data which should be serialized to JSON.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getConstantName();
    }

    /**
     * Forbids the implicit creation of enum instances without own constructors.
     */
    private function __construct() {}
}
