<?php

namespace TradusBundle\Tests\Entity;

use TradusBundle\Entity\Category;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Tests\AbstractEntityTest;

/**
 * Class OfferTest
 * @package TradusBundle\Tests\Entity
 */
class OfferTest extends AbstractEntityTest {
    const TEST_CLASS = 'TradusBundle\Entity\Offer';

    /**
     * @var Seller
     */
    protected $seller;

    /**
     * @var Offer
     */
    protected $offer;

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
        $this->offer = new Offer();
        $this->seller = new Seller();
    }

    /**
     * Test the offer constraints.
     */
    public function testOfferConstraints() {
        // Fetch the required fields.
        $expectedRequiredFields = $this->getRequiredFieldsInClass(self::TEST_CLASS);

        /**
         * Check Required fields
         */
        $violationList =  $this->validator->validate($this->offer);

        /* make sure that we have more then one required field, we expect more then 1 */
        $this->assertGreaterThan(1, count($violationList), "Expected more then 1 required field to test.");

        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set the seller */
        $this->offer->setSeller($this->seller);
        $expectedRequiredFields--;
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set model */
        $this->offer->setModel("This is a very nice model");
        $expectedRequiredFields--;
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set category */
        $this->offer->setCategory(new Category());
        $expectedRequiredFields--;
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals($expectedRequiredFields, count($violationList));

        /* Set Make */
        $this->offer->setMake(new Make());
        $expectedRequiredFields--;
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals($expectedRequiredFields, count($violationList));

        $this->offer->setAdId('just_somthing');
        $violationList =  $this->validator->validate($this->offer);


        /* Did we test all required fields */
        $this->assertEquals($violationList->count(), 0,"Number of ". $violationList->count() ." required fields not tested.");


        /**
         *  TEST VARIATIONS
         */

        /* Check status is valid */
        $this->offer->setStatus(8);
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals(1, count($violationList));

        $this->offer->setStatus(Offer::STATUS_ONLINE);
        $violationList =  $this->validator->validate($this->offer);
        $this->assertEquals(0, count($violationList));
    }

    public function testBumpUp() {
        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_PREMIUM));

        // New offers will generate sortIndex automaticly when not set yet.
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );

        // When sortindex is set we expect the same to be returned
        $expectedSortIndex->modify('-1 month');
        $this->offer->setSortIndex($expectedSortIndex);
        $this->assertEquals($expectedSortIndex->format("Y-m-d H:i:s"), $this->offer->getSortIndex()->format("Y-m-d H:i:s") );

        // When the seller is type free then we expext sortindex to be 3 months in the past
        // New offers will generate sortIndex automaticly when not set yet. (don't care about minutes and seconds)
        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_FREE));
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->modify('-3 month')->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );

        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_PACKAGE_FREE));
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->modify('-1 week')->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );

        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_PACKAGE_BRONZE));
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );

        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_PACKAGE_SILVER));
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );

        $this->offer = $this->getMockOffer();
        $this->offer->setSeller($this->offer->getSeller()->setSellerType(Seller::SELLER_TYPE_PACKAGE_GOLD));
        $expectedSortIndex = new \DateTime(self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($expectedSortIndex->modify('+12 hour')->format("Y-m-d H"), $this->offer->getSortIndex()->format("Y-m-d H") );


        // Test a bumpUP
        $oldSortIndex = $this->offer->getSortIndex();
        $this->offer->bumpUp();
        $newSortindex = $this->offer->getSortIndex();
        $this->assertNotEquals($oldSortIndex->format("Y-m-d H"), $newSortindex->format("Y-m-d H") );

        $expectedSortIndex = new \DateTime();
        $this->assertEquals($expectedSortIndex->format("Y-m-d H"), $newSortindex->format("Y-m-d H"));

        $this->offer->bumpUp('-12 hour');
        $newSortindex = $this->offer->getSortIndex();
        $expectedSortIndex = new \DateTime();
        $expectedSortIndex->modify('-12 hour');
        $this->assertEquals($expectedSortIndex->format("Y-m-d H"), $newSortindex->format("Y-m-d H"));
    }

    public function testGetDescriptionByLocale() {
        $this->offer = $this->getMockOffer();
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $offerDescription = $this->offer->getDescriptionByLocale($locale);
            $this->assertEquals('UNIT_TEST_DESCRIPTION_'.$locale, $offerDescription);
        }
    }

    public function testGetTitleByLocale() {
        $this->offer = $this->getMockOffer();
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $offerTitle = $this->offer->getTitleByLocale($locale);
            $this->assertEquals('UNIT_TEST_TITLE_'.$locale, $offerTitle);
        }
    }

    public function testGetUrlByLocale() {
        $this->offer = $this->getMockOffer();
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $offerUrl = $this->offer->getUrlByLocale($locale);
            $this->assertEquals('/'.$locale.'/category1-'.$locale.'/category2-'.$locale.'/category3-'.$locale.'/slug-UNIT_TEST_MAKE/UNIT_TEST_TITLE_SLUG_'.$locale, $offerUrl);
        }
    }

    public function testGenerateSolrPayload() {
        $this->offer = $this->getMockOffer();

        $solrPayLoad = $this->offer->generateSolrPayload();

        // Check offer data
        $this->assertEquals($solrPayLoad['offer_id'], self::OFFER_DATA[Offer::FIELD_OFFER_ID]);
        $this->assertEquals($solrPayLoad['model'], self::OFFER_DATA[Offer::FIELD_MODEL]);
        $this->assertEquals($solrPayLoad['ad_id_facet_string'], self::OFFER_DATA[Offer::FIELD_AD_ID]);
        $this->assertEquals($solrPayLoad['create_date'], self::OFFER_DATA[Offer::FIELD_CREATED_AT]);
        $this->assertEquals($solrPayLoad['make'], self::OFFER_DATA['MAKE_NAME']);
        $this->assertEquals($solrPayLoad['price'], self::OFFER_DATA[Offer::FIELD_PRICE]);
        $this->assertEquals($solrPayLoad['thumbnail'], 'https://unit.test.image.0');
        $this->assertEquals($solrPayLoad['category'][0], 1);
        $this->assertEquals($solrPayLoad['category'][1], 2);
        $this->assertEquals($solrPayLoad['category'][2], 3);
        $this->assertEquals($solrPayLoad['sort_index'], self::OFFER_DATA[Offer::FIELD_CREATED_AT]);

        // Check offers category names?
        foreach (Offer::SUPPORTED_LOCALES as $locale) {
            $this->assertEquals($solrPayLoad['category_name_'.$locale][0], 'category1-'.$locale);
            $this->assertEquals($solrPayLoad['category_name_'.$locale][1], 'category2-'.$locale);
            $this->assertEquals($solrPayLoad['category_name_'.$locale][2], 'category3-'.$locale);
            $this->assertEquals($solrPayLoad['offer_url_'.$locale], '/'.$locale.'/category1-'.$locale.'/category2-'.$locale.'/category3-'.$locale.'/slug-UNIT_TEST_MAKE/UNIT_TEST_TITLE_SLUG_'.$locale);
            $this->assertEquals($solrPayLoad['title_'.$locale], 'UNIT_TEST_TITLE_'.$locale);
            $this->assertEquals($solrPayLoad['description_'.$locale], 'UNIT_TEST_DESCRIPTION_'.$locale);
        }

        // Check Offers Attributes
        $this->assertEquals($solrPayLoad['year'], self::OFFER_DATA['construction_year']);
        $this->assertEquals($solrPayLoad['weight_facet_string'], self::OFFER_DATA['weight']);
        $this->assertEquals($solrPayLoad['mileage_facet_string'], self::OFFER_DATA['mileage']);
        $this->assertEquals($solrPayLoad['mileage_unit_facet_string'], self::OFFER_DATA['mileage_unit']);
        $this->assertEquals($solrPayLoad['hours_run_facet_string'], self::OFFER_DATA['hours_run']);

        // Check seller data
        $this->assertEquals($solrPayLoad['seller_city'], self::SELLER_DATA[Seller::FIELD_CITY]);
        $this->assertEquals($solrPayLoad['seller_company_name'], self::SELLER_DATA[Seller::FIELD_COMPANY_NAME]);
        $this->assertEquals($solrPayLoad['seller_type'], self::SELLER_DATA[Seller::FIELD_TYPE]);
        $this->assertEquals($solrPayLoad['seller_country'], self::SELLER_DATA[Seller::FIELD_COUNTRY]);
        $this->assertEquals($solrPayLoad['seller_address'], self::SELLER_DATA[Seller::FIELD_ADDRESS]);
        $this->assertEquals($solrPayLoad['seller_id'], self::SELLER_DATA[Seller::FIELD_SELLER_ID]);
        $this->assertEquals($solrPayLoad['seller_url'], self::SELLER_DATA[Seller::FIELD_SLUG]); // WHy is this the slug?
        $this->assertEquals($solrPayLoad['images_count_facet_int'], 6);

        // Checks Conversion from non-euro to euro
        $this->offer = $this->getMockOffer();
        $this->offer->setCurrency('USD');
        $this->offer->setPrice(100);
        $exchangeRate = $this->getMockExchangeRate('USD', 1.2);

        $solrPayLoad = $this->offer->generateSolrPayload(null, $exchangeRate);
        $this->assertEquals(ceil(100/1.2), $solrPayLoad['price']);
    }
}
