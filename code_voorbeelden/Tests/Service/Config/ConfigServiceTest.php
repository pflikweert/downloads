<?php

namespace TradusBundle\Tests\Service\Config;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TradusBundle\Service\Config\ConfigService;

class ConfigServiceTest extends KernelTestCase {
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @inheritdoc
     */
    public function setUp() {
        $this->configService = new ConfigService();
    }

    public function testGetSetting() {
        $configResult = $this->configService->getSetting('unitTest');
        $this->assertEquals('unitTest', $configResult->getName());
        $this->assertEquals(false, $configResult->getRawValue());
        $this->assertEquals('Unit test value', $configResult->getDefaultValue());
        $this->assertEquals('Unit test value', $configResult->getValue());
        $this->assertEquals(null, $configResult->getValue(true));
    }

}