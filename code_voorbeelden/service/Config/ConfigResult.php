<?php

namespace TradusBundle\Service\Config;

use TradusBundle\Entity\Configuration;

class ConfigResult {
    const DATA_NAME             = 'name';
    const DATA_VALUE            = 'value';
    const DATA_DISPLAY_NAME     = 'display_name';
    const DATA_DEFAULT_VALUE    = 'default_value';
    const DATA_GROUP            = 'group';
    const DATA_VALUE_TYPE       = 'value_type';
    const DATA_POSSIBLE_VALUES  = 'possible_values';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var Configuration
     */
    protected $configurationEntity;

    public function __construct(array $options = []) {
        if (count($options)) {
            $this->parseOptions($options);
        }
    }

    /**
     * Set internal values
     * @param array $options
     */
    public function parseOptions(array $options) {
        foreach ($options as $name => $value) {
            switch($name) {
                case self::DATA_NAME:
                case self::DATA_DISPLAY_NAME;
                case self::DATA_VALUE:
                case self::DATA_VALUE_TYPE:
                case self::DATA_DEFAULT_VALUE:
                case self::DATA_GROUP:
                case self::DATA_POSSIBLE_VALUES:
                    $this->setData($name, $value);
                    break;
            }
        }
    }

    /**
     * @param bool $fallbackToDefaultValue
     * @return bool|mixed|null
     */
    public function getValue($disableFallbackToDefault = false) {
        $result = $this->getRawValue();
        if ($result === null && $disableFallbackToDefault === false) {
            $result = $this->getDefaultValue();
        }
        return $result;
    }

    public function getRawValue() {
        return $this->getData(self::DATA_VALUE);
    }

    /**
     * @return bool|mixed
     */
    public function getName() {
        return $this->getData(self::DATA_NAME);
    }

    /**
     * @return bool|mixed
     */
    public function getDefaultValue() {
        return $this->getData(self::DATA_DEFAULT_VALUE);
    }

    public function getGroup() {
        return $this->getData(self::DATA_GROUP);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    protected function getData(string $name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    protected function setData(string $name, $value) {
        $this->data[$name] = $value;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfigurationEntity(Configuration $configuration) {
        $this->configurationEntity = $configuration;
        $this->setData(self::DATA_NAME, $configuration->getName());
        $this->setData(self::DATA_VALUE, $configuration->getValue());
        $this->setData(self::DATA_GROUP, $configuration->getGroup());
    }

    /**
     * @return Configuration
     */
    public function getConfigurationEntity() {
        return $this->configurationEntity;
    }
}