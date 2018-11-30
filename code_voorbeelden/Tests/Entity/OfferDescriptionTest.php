<?php

namespace TradusBundle\Tests\Entity;

use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferDescription;
use TradusBundle\Tests\AbstractEntityTest;

/**
 * Class OfferDescriptionTEst
 * @package TradusBundle\Tests\Entity
 */
class OfferDescriptionTest extends AbstractEntityTest {
    const TEST_CLASS = 'TradusBundle\Entity\OfferDescription';

    /**
     * @var Offer
     */
    protected $offer;

    /**
     * @var OfferDescription
     */
    protected $offerDescription;

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
        $this->offer = new Offer();
        $this->offerDescription = new OfferDescription();
    }

    /**
     * Test the offer constraints.
     */
    public function testOfferDescriptionsConstraints() {
        // Fetch the required fields.
        $expectedRequiredFields = $this->getRequiredFieldsInClass(self::TEST_CLASS);

        /**
         * Check Required fields
         */
        $violationList =  $this->validator->validate($this->offerDescription);

        // Make sure that we have more then one required field, we expect more then 1.
        $this->assertGreaterThan(1, count($violationList), "Expected more then 1 required field to test.");

        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set required field locale */
        $this->offerDescription->setLocale("nl");
        $violationList =  $this->validator->validate($this->offerDescription);
        $expectedRequiredFields--;
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set required field title slug */
        $this->offerDescription->setTitleSlug("title_slug");
        $violationList =  $this->validator->validate($this->offerDescription);
        $expectedRequiredFields--;
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set required offer */
        $this->offerDescription->setOffer($this->offer);
        $violationList =  $this->validator->validate($this->offerDescription);
        $expectedRequiredFields--;
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set required title */
        $this->offerDescription->setTitle("This is a nice title to test");
        $violationList =  $this->validator->validate($this->offerDescription);
        $expectedRequiredFields--;
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Did we test all required fields */
        $this->assertEquals($violationList->count(), 0,"Number of ". $violationList->count() ." required fields not tested.");

        /**
         *  TEST VARIATIONS
         */

        /* Check locale is valid */
        $this->offerDescription->setLocale("longerThenMax5Chars");
        $violationList =  $this->validator->validate($this->offerDescription);
        $this->assertEquals(1, count($violationList));

        $this->offerDescription->setLocale("nl");
        $violationList =  $this->validator->validate($this->offerDescription);
        $this->assertEquals(0, count($violationList));
    }
}
