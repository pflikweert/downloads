<?php

namespace TradusBundle\Service\Config;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\Configuration;
use TradusBundle\Repository\ConfigurationRepository;

/**
 * Class ConfigService
 *
 * @package TradusBundle\Service\Config
 */
class ConfigService implements ConfigServiceInterface {

    /**
     * All the settings
     * @var array
     */
    protected $settings = [];

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;


    public function __construct(EntityManagerInterface $entityManager = null) {
        $this->entityManager = $entityManager;
        $this->loadSettings();
    }

    /**
     * @param string $name
     * @return ConfigResult
     */
    public function getSetting(string $name) {
        return $this->settings[$name];
    }

    /**
     * @param string $name
     * @return bool|mixed|null
     */
    public function getSettingValue(string $name) {
        return $this->getSetting($name)->getValue();
    }


    /**
     * Loads all settings
     */
    protected function loadSettings() {
        $this->parseDefaultSettingsFromInterface();
        $this->loadFromDatabase();
        $this->insertNewConfigurationInDatabase();
    }

    /**
     * Parsing default settings as defined in the interface
     */
    protected function parseDefaultSettingsFromInterface() {
        foreach (self::DEFAULT_SETTINGS as $setting) {
            $configResult = new ConfigResult($setting);
            $this->settings[$configResult->getName()] = $configResult;
        }
    }

    /**
     * Loads all setting from the database
     */
    protected function loadFromDatabase() {
        if ($this->entityManager) {
            /* @var ConfigurationRepository $configurationRepository */
            $configurationRepository = $this->entityManager->getRepository('TradusBundle:Configuration');
            $configurations = $configurationRepository->getAllConfigurations();

            /* @var \TradusBundle\Entity\Configuration $configuration */
            if ($configurations) {
                foreach ($configurations as $configuration) {
                    /* @var \TradusBundle\Service\Config\ConfigResult $configResult */
                    if (isset($this->settings[$configuration->getName()])) {
                        $configResult = $this->settings[$configuration->getName()];
                    } else {
                        $configResult = new ConfigResult();
                    }
                    $configResult->setConfigurationEntity($configuration);
                    $this->settings[$configuration->getName()] = $configResult;
                }
            }
        }
    }

    /**
     * Insert new values as defined in the Interface into the database
     */
    protected function insertNewConfigurationInDatabase() {
        if ($this->entityManager) {
            /* @var \TradusBundle\Service\Config\ConfigResult $configResult */
            foreach ($this->settings as $name => $configResult) {

                $entity = $configResult->getConfigurationEntity();

                if ($entity === null) {
                    $configuration = new Configuration();
                    $configuration->setName($configResult->getName());
                    $configuration->setGroup($configResult->getGroup());
                    $configuration->setValue($configResult->getValue());

                    $this->entityManager->persist($configuration);
                    $this->entityManager->flush();
                    $configResult->setConfigurationEntity($configuration);
                    $this->settings[$name] = $configResult;
                }
            }
        }
    }
}