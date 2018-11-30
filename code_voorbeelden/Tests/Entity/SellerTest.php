<?php

namespace TradusBundle\Tests\Entity;

use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Tests\AbstractEntityTest;

/**
 * Class SellerTest
 * @package TradusBundle\Tests\Entity
 */
class SellerTest extends AbstractEntityTest {
    const thisIsNotValidEmail = "thisIsNotValidEmail";
    const thisIsValidEmail = "donald@duck.com";

    const SELLER_TYPE_VALID = Seller::SELLER_TYPE_FREE;
    const SELLER_TYPE_INVALID = 1337;

    const TEST_CLASS = 'TradusBundle\Entity\Seller';

    /**
     * @var Seller
     */
    protected $seller;

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
        $this->seller = new Seller();
    }

    /**
     * Test the seller constraints.
     */
    public function testSellerConstraints() {
        // Fetch the required fields.
        $expectedRequiredFields = $this->getRequiredFieldsInClass(self::TEST_CLASS);
        // Lowered by one because seller_type has a default value;
        $expectedRequiredFields--;

        // Check violations.
        $violationList =  $this->validator->validate($this->seller);

        // Make sure that we have more then one required field, we expect more then 1.
        $this->assertGreaterThan(1, count($violationList), "Expected more then 1 required field to test.");
        $this->assertEquals($expectedRequiredFields, count($violationList));

        // Basic test, to see if value is properly set.
        $this->seller->setEmail(self::thisIsNotValidEmail);
        $this->assertEquals($this->seller->getEmail(), self::thisIsNotValidEmail);

        // Now with good email, resolve 1 validation error.
        $expectedRequiredFields--;
        $this->seller->setEmail(self::thisIsValidEmail);
        $violationList =  $this->validator->validate($this->seller);

        $this->assertEquals($expectedRequiredFields, count($violationList));

        // Set all required fields.
        $this->seller->setCountry(self::SELLER_DATA[SellerInterface::FIELD_COUNTRY]);
        $this->seller->setSlug("test_compagny_1"); // TODO: is this not generated somewhere?
        $this->seller->setCompanyName(self::SELLER_DATA[SellerInterface::FIELD_COMPANY_NAME]);
        $this->seller->setStatus(Seller::STATUS_ONLINE);
        $this->seller->setSellerType(self::SELLER_TYPE_VALID);

        $violationList =  $this->validator->validate($this->seller);

        /* Did we test all required fields */
        $this->assertEquals($violationList->count(), 0,"Number of ". $violationList->count() ." required fields not tested.");



        // Should not be able to set non-existing status.
        $this->seller->setStatus(12345678);

        // Should not be able to set non-existing type.
        $this->seller->setSellerType(self::SELLER_TYPE_INVALID);
        $violationList = $this->validator->validate($this->seller);
        $this->assertEquals(2, count($violationList));
    }

    public function testGetBumpModifierForSellerType() {
        $this->assertEquals(false,Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_FREE));
        $this->assertEquals(false,Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_PACKAGE_FREE));
        $this->assertEquals('-4 week',Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_PREMIUM));
        $this->assertEquals('-4 week',Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_PACKAGE_BRONZE));
        $this->assertEquals('-2 week',Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_PACKAGE_SILVER));
        $this->assertEquals('-1 week',Seller::getBumpModifierForSellerType(Seller::SELLER_TYPE_PACKAGE_GOLD));
    }

    public function testSellerTypes() {
        $this->seller = $this->getMockSeller();

        // Expecting the following seller types to be valid
        foreach(Seller::getValidSellerTypes() as $sellerType) {
            $this->seller->setSellerType($sellerType);
            $violationList = $this->validator->validate($this->seller);
            $this->assertEquals(0, count($violationList));
        }

        // Expect error with unexpected seller_type
        $this->seller->setSellerType(self::SELLER_TYPE_INVALID);
        $violationList = $this->validator->validate($this->seller);
        $this->assertEquals(1, count($violationList));
    }
}
