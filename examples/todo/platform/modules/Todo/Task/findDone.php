<?php
namespace Todo\Task;

require_once __DIR__.'/findDoneJsonConverter.php';
require_once __DIR__.'/findDoneArrayConverter.php';
require_once __DIR__.'/../Task.php';

/**
 * Generated from NGS DSL
 *
 *
 * @package Todo
 * @version 0.9.9 beta
 */
class findDone extends \NGS\Patterns\Specification
{

    /**
     * Constructs object using a key-property array or instance of class "Todo\Task\findDone"
     *
     * @param array|void $data key-property array or instance of class "Todo\Task\findDone" or pass void to provide all fields with defaults
     */
    public function __construct($data = array())
    {
        if (is_array($data)) {
            $this->fromArray($data);
        } else {
            throw new \InvalidArgumentException('Constructor parameter must be an array! Type was: '.\NGS\Utils::getType($data));
        }
    }

    /**
     * Supply default values for uninitialized properties
     *
     * @param array $data key-property array which will be filled in-place
     */
    private static function provideDefaults(array &$data) {}

    /**
     * Constructs object from a key-property array
     *
     * @param array $data key-property array
     */
    private function fromArray(array $data)
    {
        $this->provideDefaults($data);

        if (count($data) !== 0 && \NGS\Utils::WarningsAsErrors())
            throw new \InvalidArgumentException('Superflous array keys found in "Todo\Task\findDone" constructor: '.implode(', ', array_keys($data)));
    }

// ============================================================================

    /**
     * Property getter which throws Exceptions on invalid access
     *
     * @param string $name Property name
     *
     * @return mixed
     */
    public function __get($name)
    {

        throw new \InvalidArgumentException('Property "'.$name.'" in class "Todo\Task\findDone" does not exist and could not be retrieved!');
    }

// ============================================================================

    /**
     * Property existence checker
     *
     * @param string $name Property name to check for existence
     *
     * @return bool will return true if and only if the propery exist and is not null
     */
    public function __isset($name)
    {

        return false;
    }

    /**
     * Property setter which checks for invalid access to specification properties and enforces proper type checks
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set($name, $value)
    {
        throw new \InvalidArgumentException('Property "'.$name.'" in class "Todo\Task\findDone" does not exist and could not be set!');
    }

    /**
     * Will unset a property if it exists, but throw an exception if it is not nullable
     *
     * @param string $name Property name to unset (set to null)
     */
    public function __unset($name) {}

    public function toJson()
    {
        return \Todo\Task\findDoneJsonConverter::toJson($this);
    }

    public static function fromJson($item)
    {
        return \Todo\Task\findDoneJsonConverter::fromJson($item);
    }

    public function __toString()
    {
        return 'Todo\Task\findDone'.$this->toJson();
    }

    public function __clone()
    {
        return \Todo\Task\findDoneArrayConverter::fromArray(\Todo\Task\findDoneArrayConverter::toArray($this));
    }

    public function toArray()
    {
        return \Todo\Task\findDoneArrayConverter::toArray($this);
    }
}