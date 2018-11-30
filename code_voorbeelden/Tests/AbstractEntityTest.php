<?php

namespace TradusBundle\Tests;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;
use Doctrine\Common\Annotations\AnnotationReader;
use TradusBundle\Entity\Attribute;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryTranslation;
use TradusBundle\Entity\ExchangeRate;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferAttribute;
use TradusBundle\Entity\OfferDescription;
use TradusBundle\Entity\OfferImage;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Entity\TradusUser;

/**
 * Class AbstractTest
 *
 * @package TradusBundle\Tests
 */
abstract class AbstractEntityTest extends KernelTestCase {
    // Constant used to mark a field as required.
    const FIELD_REQUIRED_CONSTRAINT = 'Symfony\Component\Validator\Constraints\NotBlank';

    const OFFER_DATA = [
        OfferInterface::FIELD_OFFER_ID => 1234,
        OfferInterface::FIELD_MODEL => 'UNIT_TEST_MODEL',
        OfferInterface::FIELD_CATEGORY => 9,
        OfferInterface::FIELD_MAKE => 1300,
        OfferInterface::FIELD_PRICE => 100.00,
        OfferInterface::FIELD_CURRENCY => 'EUR',
        OfferInterface::FIELD_MILEAGE => 12000,
        OfferInterface::FIELD_STATUS => OfferInterface::STATUS_ONLINE,
        OfferInterface::FIELD_AD_ID => 'UNIT_TEST_AD_ID',
        OfferInterface::FIELD_CREATED_AT => "2018-03-01 12:10:10",
        OfferInterface::FIELD_BUMPED_AT  => "2018-03-01 12:10:10",
        'MAKE_NAME' => 'UNIT_TEST_MAKE',
        OfferInterface::FIELD_CONSTRUCTION_YEAR => 2018,
        OfferInterface::FIELD_WEIGHT => 500,
        OfferInterface::FIELD_MILEAGE => 500,
        OfferInterface::FIELD_MILEAGE_UNIT=> 'km',
        OfferInterface::FIELD_HOURS_RUN => 6000,
        OfferInterface::FIELD_SLUG => 'UNIT_TEST_TITLE_SLUG_',
        OfferInterface::FIELD_V1_OFFER_ID => 1,
    ];

    const SELLER_DATA = [
        SellerInterface::FIELD_CITY => 'UNIT_TEST_CITY',
        SellerInterface::FIELD_COUNTRY => 'NL',
        SellerInterface::FIELD_COMPANY_NAME => 'UNIT_TEST_COMPANY',
        SellerInterface::FIELD_SELLER_ID => 1,
        SellerInterface::FIELD_SLUG => 'UNIT_TEST_COMPANY-1',
        SellerInterface::FIELD_EMAIL => 'unit@test.com',
        SellerInterface::FIELD_ADDRESS => 'UNIT_TEST_ADDRESS',
        SellerInterface::FIELD_STATUS => SellerInterface::STATUS_ONLINE,
        SellerInterface::FIELD_TYPE => SellerInterface::SELLER_TYPE_PREMIUM,
    ];

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * Basic setup.
     */
    public function setUp() {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        global $kernel;
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->validator = static::$kernel->getContainer()->get('validator');
    }


    /**
     * @param string $columnName
     * @param ConstraintViolationList $violationList
     * @return mixed|null|ConstraintViolation|\Symfony\Component\Validator\ConstraintViolationInterface
     */
    public function findViolationByColumn(string $columnName, ConstraintViolationList $violationList) {
        $foundViolation = null;
        if ($violationList->count()) {
            foreach ($violationList as $violation) {
                /* @var ConstraintViolation $violation */
                if($violation->getPropertyPath() == $columnName)
                    $foundViolation = $violation;
            }
        }
        return $foundViolation;
    }

    /**
     * Function for obtaining the required fields from a given class.
     *
     * @param string $className
     *   The class to be read.
     *
     * @return int
     *   Returns the amount of required fields in a given class.
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getRequiredFieldsInClass(string $className) {

        // Fetch meta data from class
        $metadata = $this->entityManager->getClassMetadata($className);
        $fieldNames = $metadata->getFieldNames();
        $associationNames = $metadata->getAssociationNames();

        // Use the annotation reader to read the annotations in a given class.
        $annotationReader = new AnnotationReader();

        $required = [];
        foreach (array_merge($fieldNames,$associationNames) as $fieldName) {
            $reflectionProperty = new \ReflectionProperty($className, $fieldName);
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            // Map the property annotations to their respective namespace / class string.
            $annotations = array_map(function($input) {
                return get_class($input);
            }, $propertyAnnotations);

            // Check if a property has the notblank assertion.
            if (in_array(self::FIELD_REQUIRED_CONSTRAINT, $annotations)) {
                $required[] = $fieldName;
            }
        }

        return count($required);
    }

    /**
     * @param array $options
     * @return TradusUser
     */
    public function getMockTradusUser(array $options = []) {
        /* @var TradusUser $tradusUser */
        $tradusUser = $this->getMockBuilder(TradusUser::class)->setMethods(['getId'])->getMock();
        $tradusUser->expects($this->any())->method('getId')->willReturn(123);
        $tradusUser->setValues($options);
        return $tradusUser;
    }

    /**
     * @param String $makeName
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockMake(String $makeName = '') {
        if (empty($makeName)) {
            $makeName = self::OFFER_DATA['MAKE_NAME'];
            $makeSlug = 'slug-'.self::OFFER_DATA['MAKE_NAME'];
        } else {
            $slugify = new Slugify();
            $makeSlug = $slugify->slugify($makeName);
        }
        $make = $this->getMockBuilder(Make::class)->setMethods(['getId','getName', 'getSlug'])->getMock();
        $make->expects($this->any())->method('getId')->willReturn(self::OFFER_DATA[OfferInterface::FIELD_MAKE]);
        $make->expects($this->any())->method('getName')->willReturn($makeName);
        $make->expects($this->any())->method('getSlug')->willReturn($makeSlug);
        return $make;
    }

    /**
     * Generates a basic  mock offer for unit testing
     *
     * @return Offer
     */
    public function getMockOffer(){
        /* @var Offer $offer */
        $offer = $this->getMockBuilder(Offer::class)->setMethods(['getDescriptions','getImages'])->getMock();
        $offer->expects($this->any())->method('getDescriptions')->willReturn($this->getMockOfferDescriptions());
        $offer->expects($this->any())->method('getImages')->willReturn($this->getMockOfferImages());

        $offer->setCategory($this->getMockCategory());
        $offer->setMake($this->getMockMake());
        $offer->setSeller($this->getMockSeller());

        $offer->setId(self::OFFER_DATA[OfferInterface::FIELD_OFFER_ID]);
        $offer->setStatus(self::OFFER_DATA[OfferInterface::FIELD_STATUS]);
        $offer->setPrice(self::OFFER_DATA[OfferInterface::FIELD_PRICE]);
        $offer->setCurrency(self::OFFER_DATA[OfferInterface::FIELD_CURRENCY]);
        $offer->setModel(self::OFFER_DATA[OfferInterface::FIELD_MODEL]);
        $offer->setAdId(self::OFFER_DATA[OfferInterface::FIELD_AD_ID]);
        $offer->setCreatedAt(new \DateTime(self::OFFER_DATA[OfferInterface::FIELD_CREATED_AT]));
        //$offer->setBumpedAt(new \DateTime(self::OFFER_DATA[OfferInterface::FIELD_BUMPED_AT]));
        $offer->setV1Id(OfferInterface::FIELD_V1_OFFER_ID);

        $offer->addAttribute($this->getMockOfferAttribute('construction_year', self::OFFER_DATA['construction_year']));
        $offer->addAttribute($this->getMockOfferAttribute('weight', self::OFFER_DATA['weight']));
        $offer->addAttribute($this->getMockOfferAttribute('mileage', self::OFFER_DATA['mileage']));
        $offer->addAttribute($this->getMockOfferAttribute('mileage_unit', self::OFFER_DATA['mileage_unit']));
        $offer->addAttribute($this->getMockOfferAttribute('hours_run', self::OFFER_DATA['hours_run']));

        return $offer;
    }

    function getMockOfferAttribute($name, $content) {
        $attribute = $this->getMockBuilder(Attribute::class)->setMethods(['getName', 'getContent'])->disableOriginalConstructor()->getMock();
        $attribute->expects($this->any())->method('getName')->willReturn($name);
        $attribute->expects($this->any())->method('getContent')->willReturn($content);

        $offerAttribute = $this->getMockBuilder(OfferAttribute::class)->setMethods(['getAttribute', 'getContent'])->disableOriginalConstructor()->getMock();
        $offerAttribute->expects($this->any())->method('getAttribute')->willReturn($attribute);
        $offerAttribute->expects($this->any())->method('getContent')->willReturn($content);

        return $offerAttribute;
    }

    /**
     * @return \Entity\Category|\PHPUnit\Framework\MockObject\MockObject
     */
    function getMockCategory() {
        /* @var \Entity\Category $category3 */
        /* @var \Entity\Category $category2 */
        /* @var \Entity\Category $category1 */
        $category1 = $this->getMockBuilder(Category::class)->setMethods(['getDepth','getTranslations','getId','getParent','getChildren'])->disableOriginalConstructor()->getMock();
        $category1->expects($this->any())->method('getDepth')->willReturn(1);
        $category1->expects($this->any())->method('getId')->willReturn(1);
        $category1->expects($this->any())->method('getTranslations')->willReturn($this->getMockCategoryTranslations('category1'));
        $category1->expects($this->any())->method('getParent')->willReturn(null);

        $category2 = $this->getMockBuilder(Category::class)->setMethods(['getDepth','getTranslations','getId','getParent','getChildren'])->disableOriginalConstructor()->getMock();
        $category2->expects($this->any())->method('getDepth')->willReturn(2);
        $category2->expects($this->any())->method('getId')->willReturn(2);
        $category2->expects($this->any())->method('getTranslations')->willReturn($this->getMockCategoryTranslations('category2'));
        $category2->expects($this->any())->method('getParent')->willReturn($category1);

        $category3 = $this->getMockBuilder(Category::class)->setMethods(['getDepth','getTranslations','getId','getParent','getChildren'])->disableOriginalConstructor()->getMock();
        $category3->expects($this->any())->method('getDepth')->willReturn(3);
        $category3->expects($this->any())->method('getId')->willReturn(3);
        $category3->expects($this->any())->method('getTranslations')->willReturn($this->getMockCategoryTranslations('category3'));
        $category3->expects($this->any())->method('getParent')->willReturn($category2);
        $category3->expects($this->any())->method('getChildren')->willReturn(null);

        $category2->expects($this->any())->method('getChildren')->willReturn(array($category3));
        $category1->expects($this->any())->method('getChildren')->willReturn(array($category2,$category3));

        return $category3;
    }

    function getMockCategoryTranslations($name = "unit_test") {
        $categoryTranslations = [];
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $categoryTranslation = $this->getMockBuilder(CategoryTranslation::class)->setMethods(['getName', 'getSlug', 'getLocale'])->disableOriginalConstructor()->getMock();
            $categoryTranslation->expects($this->any())->method('getName')->willReturn($name.'-'.$locale);
            $categoryTranslation->expects($this->any())->method('getSlug')->willReturn($name.'-'.$locale);
            $categoryTranslation->expects($this->any())->method('getLocale')->willReturn($locale);
            $categoryTranslations[] = $categoryTranslation;
        }
        return $categoryTranslations;
    }

    function getMockOfferDescriptions() {
        $offerDescriptions = [];
        foreach(Offer::SUPPORTED_LOCALES as $locale) {
            $offerDescription = $this->getMockBuilder(OfferDescription::class)->setMethods(['getLocale','getTitleSlug','getTitle','getDescription'])->disableOriginalConstructor()->getMock();
            $offerDescription->expects($this->any())->method('getLocale')->willReturn($locale);
            $offerDescription->expects($this->any())->method('getTitleSlug')->willReturn("UNIT_TEST_TITLE_SLUG_".$locale);
            $offerDescription->expects($this->any())->method('getTitle')->willReturn("UNIT_TEST_TITLE_".$locale);
            $offerDescription->expects($this->any())->method('getDescription')->willReturn("UNIT_TEST_DESCRIPTION_".$locale);
            $offerDescriptions[] = $offerDescription;
        }

        return $offerDescriptions;
    }

    function getMockOfferImages() {
        $offerImages = [];
        for($i = 0; $i <=5; $i++) {
            /* @var OfferImage $offerImage */
            $offerImage = $this->getMockBuilder(OfferImage::class)->setMethods(['getUrl','getSortOrder'])->disableOriginalConstructor()->getMock();
            $offerImage->expects($this->any())->method('getUrl')->willReturn('https://unit.test.image.' . $i);
            $offerImage->expects($this->any())->method('getSortOrder')->willReturn($i);
            $offerImages[] = $offerImage;
        }
        return $offerImages;
    }

    /**
     * @param string $currency
     * @param float $rate
     * @return ExchangeRate
     */
    function getMockExchangeRate($currency = 'EUR', $rate = 1.0) {
        /* @var ExchangeRate $exchangeRate */
        $exchangeRate = $this->getMockBuilder(ExchangeRate::class)->setMethods(['getCurrency','getRate'])->disableOriginalConstructor()->getMock();
        $exchangeRate->expects($this->any())->method('getCurrency')->willReturn($currency);
        $exchangeRate->expects($this->any())->method('getRate')->willReturn($rate);
        return $exchangeRate;

    }

    /**
     * Generates a basic seller for unit testing
     *
     * @return Seller
     */
    public function getMockSeller() {
        /* @var \TradusBundle\Entity\Seller $seller */
        $seller = $this->getMockBuilder(Seller::class)->setMethods(['getId'])->disableOriginalConstructor()->getMock();
        $seller->expects($this->any())->method('getId')->willReturn(self::SELLER_DATA[SellerInterface::FIELD_SELLER_ID]);

        $seller->setCity(self::SELLER_DATA[SellerInterface::FIELD_CITY]);
        $seller->setAddress(self::SELLER_DATA[SellerInterface::FIELD_ADDRESS]);
        $seller->setCountry(self::SELLER_DATA[SellerInterface::FIELD_COUNTRY]);
        $seller->setCompanyName(self::SELLER_DATA[SellerInterface::FIELD_COMPANY_NAME]);
        $seller->setEmail(self::SELLER_DATA[SellerInterface::FIELD_EMAIL]);
        $seller->setStatus(self::SELLER_DATA[SellerInterface::FIELD_STATUS]);
        $seller->setSellerType(self::SELLER_DATA[SellerInterface::FIELD_TYPE]);
        $seller->setSlug(self::SELLER_DATA[SellerInterface::FIELD_SLUG]);
        return $seller;
    }
}