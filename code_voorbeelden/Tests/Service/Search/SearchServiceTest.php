<?php

namespace TradusBundle\Tests\Service\Search;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\Seller;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\MakeRepository;
use TradusBundle\Service\Search\AdapterCurl;
use TradusBundle\Service\Search\Client;
use TradusBundle\Service\Search\Query;
use TradusBundle\Service\Search\Response;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Tests\Entity\OfferTest;

class SearchServiceTest extends KernelTestCase {
    /* @var SearchService $searchService */
    protected $searchService;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected $offerTest;

    /**
     * @inheritdoc
     */
    public function setUp() {
        global $kernel;
        $kernel = self::bootKernel();

        $this->offerTest = new OfferTest();

        $makeRepository = $this->getMockBuilder(MakeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $makeRepository->expects($this->any())->method('getMakesBySlug')
            ->willReturnMap([
                [ ['alum-line'], [$this->offerTest->getMockMake('Alum-Name')]],
                [ ['balzum'], [$this->offerTest->getMockMake('Balzum')]],
                [ ['am-general'], [$this->offerTest->getMockMake('AM General')]],
            ]);

        $makeRepository->expects($this->any())->method('findAll')
            ->willReturn([
                $this->offerTest->getMockMake('Alum-Name'),
                $this->offerTest->getMockMake('Balzum'),
                $this->offerTest->getMockMake('AM General'),
            ]);


        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->any())->method('find')
            ->willReturnMap([
                [OfferTest::OFFER_DATA[Offer::FIELD_CATEGORY], null, null, $this->offerTest->getMockCategory()],
            ]);
        $categoryRepository->expects($this->any())->method('getL1Categories')
            ->willReturn([$this->offerTest->getMockCategory()]);
        $categoryRepository->expects($this->any())->method('getL2Categories')
            ->willReturn([$this->offerTest->getMockCategory()]);
        $categoryRepository->expects($this->any())->method('getL3Categories')
            ->willReturn([$this->offerTest->getMockCategory()]);


        /** @var EntityManager $entityManager */
        $this->entityManager = $this->createMock('\Doctrine\ORM\EntityManager');

        $this->entityManager->expects($this->any())->method('getRepository')
            ->willReturnMap([
                ['TradusBundle:Make', $makeRepository],
                ['TradusBundle:Category', $categoryRepository],
            ]);

    }

    public function prepareSearch($fileName) {
        $body = file_get_contents(__DIR__ . '/ResponseFiles/'.$fileName);
        $headers[] = "HTTP/1.1 200 OK";
        $response = new Response($body, $headers);

        /* @var \TradusBundle\Service\Search\AdapterCurl $adapter */
        $adapter = $this->getMockBuilder(AdapterCurl::class)->disableOriginalConstructor()->getMock();
        $adapter->expects($this->any())->method('execute')->willReturn($response);

        $client = new Client();
        $client->setAdapter($adapter);

        $this->searchService = new SearchService(null, $this->entityManager);
        $this->searchService->setClient($client);
    }

    /**
     *  Test facet data
     */
    public function testGetCategoryFacetDataSideWide() {
        $this->prepareSearch('GetCategoryFacetDataSideWide.body');

        $result = $this->searchService->getCategoryFacetDataSideWide();
        $facetCategory = $result->getFacetField('category');

        $this->assertEquals(44, count($facetCategory));
        $this->assertEquals(452, $facetCategory[1]);
        $this->assertEquals(411, $facetCategory[2]);
        $this->assertEquals(26, $facetCategory[3]);
        $this->assertEquals(464, $result->getNumberFound());
        $this->assertEquals(1, count($result->getDocuments()));
    }

    public function testFindLatestPremiumOffersBy() {
        $this->prepareSearch('FindLatestPremiumOffersBy.body');

        $result = $this->searchService->findLatestPremiumOffersBy(1, 500);
        $this->assertEquals(6, count($result['response']['docs']));
        $this->assertEquals(361, $result['response']['numFound']);

        $countSellerType = [
            Seller::SELLER_TYPE_FREE => 0,
            Seller::SELLER_TYPE_PREMIUM => 0,
            Seller::SELLER_TYPE_PACKAGE_FREE => 0,
            Seller::SELLER_TYPE_PACKAGE_BRONZE => 0,
            Seller::SELLER_TYPE_PACKAGE_SILVER => 0,
            Seller::SELLER_TYPE_PACKAGE_GOLD => 0,
            ];

        foreach ($result['response']['docs'] as $doc) {
            $countSellerType[$doc['seller_type']]++;
        }

        // Gold and Silver are low in the result set, but expect them in the first 6
        $this->assertLessThanOrEqual(1, $countSellerType[Seller::SELLER_TYPE_FREE]);
        $this->assertLessThanOrEqual(1, $countSellerType[Seller::SELLER_TYPE_PREMIUM]);
        $this->assertEquals(0, $countSellerType[Seller::SELLER_TYPE_PACKAGE_FREE]);
        $this->assertLessThanOrEqual(3, $countSellerType[Seller::SELLER_TYPE_PACKAGE_BRONZE]);
        $this->assertGreaterThanOrEqual(1, $countSellerType[Seller::SELLER_TYPE_PACKAGE_SILVER]);
        $this->assertGreaterThanOrEqual(2, $countSellerType[Seller::SELLER_TYPE_PACKAGE_GOLD]);
    }

    public function testFindSimilarOffersBy() {
        $this->prepareSearch('FindSimilarOffersBy.body');

        $result = $this->searchService->findSimilarOffersBy(9, "Ackermann");
        $this->assertEquals(6, count($result['response']['docs']));
        $this->assertEquals(8, $result['response']['numFound']);

        $countSellerType = [
            Seller::SELLER_TYPE_FREE => 0,
            Seller::SELLER_TYPE_PREMIUM => 0,
            Seller::SELLER_TYPE_PACKAGE_FREE => 0,
            Seller::SELLER_TYPE_PACKAGE_BRONZE => 0,
            Seller::SELLER_TYPE_PACKAGE_SILVER => 0,
            Seller::SELLER_TYPE_PACKAGE_GOLD => 0,
        ];

        foreach ($result['response']['docs'] as $doc) {
            $countSellerType[$doc['seller_type']]++;
        }

        // Gold and Silver are low in the result set, but expect them in the first 6
        // There is one SELLER_TYPE_FREE in the result set we expect it yo be filtert out of the result set
        // Result set is shuffeled so for some we expect some random numbers
        $this->assertEquals(0, $countSellerType[Seller::SELLER_TYPE_FREE]);
        $this->assertLessThanOrEqual(1, $countSellerType[Seller::SELLER_TYPE_PACKAGE_FREE]);
        $this->assertLessThanOrEqual(2, $countSellerType[Seller::SELLER_TYPE_PREMIUM]);
        $this->assertLessThanOrEqual(2, $countSellerType[Seller::SELLER_TYPE_PACKAGE_BRONZE]);
        $this->assertEquals(1, $countSellerType[Seller::SELLER_TYPE_PACKAGE_SILVER]);
        $this->assertEquals(1, $countSellerType[Seller::SELLER_TYPE_PACKAGE_GOLD]);
    }

    public function testFindByRequest() {
        /* @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->query = new ParameterBag([
            SearchService::REQUEST_FIELD_PAGE => 1,
            SearchService::REQUEST_FIELD_CAT_L1 => 1,
            SearchService::REQUEST_FIELD_QUERY => 'volvo',
            SearchService::REQUEST_FIELD_MAKE => 'alum-line',
        ]);

        // Don't care atm about the content
        $this->prepareSearch('FindLatestPremiumOffersBy.body');
        $result = $this->searchService->findByRequest($request);
        $query = $this->searchService->getQuery();

        if ($query->getEdismax())
            $this->assertEquals(['score' => Query::SORT_DESC], $query->getSorts());
        else
            $this->assertEquals([SearchService::SEARCH_FIELDS_SORT_INDEX => Query::SORT_DESC], $query->getSorts());

        $this->assertEquals(16, $query->getRows());
        $this->assertEquals(["make" => true, "category" => true, "seller_country" => true,], $query->getFacetFields());
        $this->assertEquals('category:1 AND query:("volvo") AND make:("Alum-Name")', $query->getQuery());

        // TEST DIFFERENT SORTING
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_SORT => SearchService::REQUEST_VALUE_SORT_DATE_DESC]);
        $result = $this->searchService->findByRequest($request);
        $query = $this->searchService->getQuery();
        $this->assertEquals([SearchService::SEARCH_FIELDS_CREATE_DATE => Query::SORT_DESC], $query->getSorts());

        // Test Multiple Countries
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_COUNTRY => ['NL','FR'] ]);
        $result = $this->searchService->findByRequest($request);
        $query = $this->searchService->getQuery();
        $this->assertEquals('seller_country:("NL" OR "FR")', $query->getQuery());

        // Set page number
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_PAGE => 5 ]);
        $result = $this->searchService->findByRequest($request);
        $query = $this->searchService->getQuery();
        $this->assertEquals((5-1)*16, $query->getStart());

        // Test Multiple Query Words
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_QUERY => "ackermann dxt"]);
        $result = $this->searchService->findByRequest($request);
        $query = $this->searchService->getQuery();
        $this->assertEquals('query:("ackermann" AND "dxt")', $query->getQuery());

    }

    public function testGetFacetDataResults() {
        $this->prepareSearch('findByRequest.body');
        /* @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_QUERY => 'volvo' ]);
        $result = $this->searchService->findByRequest($request);

        $facetData = $this->searchService->getFacetDataResults();
        $this->assertEquals('price', $facetData['price']['name']);
        $this->assertEquals('price', $facetData['price']['label']);
        $this->assertEquals('13776', $facetData['price']['limits']['min']);
        $this->assertEquals('999726', $facetData['price']['limits']['max']);

        $this->assertEquals('year', $facetData['year']['name']);
        $this->assertEquals('year', $facetData['year']['label']);
        $this->assertEquals('1900', $facetData['year']['limits']['min']);
        $this->assertEquals('2016', $facetData['year']['limits']['max']);

        $this->assertEquals('country', $facetData['country']['name']);
        $this->assertEquals('Netherlands', $facetData['country']['items'][0]['label']);
        $this->assertEquals('NL', $facetData['country']['items'][0]['value']);
        $this->assertEquals('464', $facetData['country']['items'][0]['resultCount']);

        $this->assertEquals('category', $facetData['category']['name']);
        $this->assertEquals('category3-en', $facetData['category']['items'][0]['label']);
        $this->assertEquals('3', $facetData['category']['items'][0]['value']);
        $this->assertEquals('26', $facetData['category']['items'][0]['resultCount']);
        $this->assertEquals('/en/search/q/volvo/', $facetData['category']['items'][0]['url']);
        $this->assertEquals('/en/search/q/volvo/', $facetData['category']['items'][0]['resetLink']);
    }

    public function testGetSearchUrls() {
        $this->prepareSearch('FindLatestPremiumOffersBy.body');
        /* @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->query = new ParameterBag([ SearchService::REQUEST_FIELD_QUERY => 'volvo' ]);
        $result = $this->searchService->findByRequest($request);

        $this->assertEquals('/nl/search/',$this->searchService->getSearchBaseUrl('nl'));
        $this->assertEquals('/nl/search/q/volvo/',$this->searchService->getSearchUrl('nl'));
        $this->assertEquals('query=volvo',$this->searchService->getSearchUrlParametersString());
        $this->assertEquals('',$this->searchService->getSearchUrlParametersString([SearchService::REQUEST_FIELD_QUERY]));

        // With Category and pagination
        $request->query = new ParameterBag([
            SearchService::REQUEST_FIELD_PAGE => 2,
            SearchService::REQUEST_FIELD_CAT_L3 => 9,
            SearchService::REQUEST_FIELD_MAKE => 'alum-line',
            'locale' => 'en',
        ]);
        $result = $this->searchService->findByRequest($request);

        $this->assertEquals('/en/search/category1-en-c1/category2-en-t2/category3-en-s3/make-alum-line/',$this->searchService->getSearchUrl('en'));
        $this->assertEquals('page=2',$this->searchService->getSearchUrlParametersString([SearchService::REQUEST_FIELD_QUERY]));

        // With Category and pagination
        $request->query = new ParameterBag([
            SearchService::REQUEST_FIELD_PAGE => 2,
            SearchService::REQUEST_FIELD_CAT_L3 => 9,
            SearchService::REQUEST_FIELD_MAKE => 'alum-line',
            SearchService::REQUEST_FIELD_SELLER_SLUG => 'pieter',
            SearchService::REQUEST_FIELD_QUERY => 'volvo',
            SearchService::REQUEST_FIELD_SORT => SearchService::REQUEST_VALUE_SORT_DATE_DESC,
            'locale' => 'en',
        ]);
        $result = $this->searchService->findByRequest($request);

        $this->assertEquals('/en/s/pieter/q/volvo/category1-en-c1/category2-en-t2/category3-en-s3/make-alum-line/',$this->searchService->getSearchUrl('en'));
        $this->assertEquals('page=2&sort=date-desc',$this->searchService->getSearchUrlParametersString([SearchService::REQUEST_FIELD_QUERY]));

        // With Category and pagination
        $request->query = new ParameterBag([
            SearchService::REQUEST_FIELD_MAKE => 'am-general+balzum',
           SearchService::REQUEST_FIELD_COUNTRY => ['NL', 'FR'],
            'locale' => 'en',
        ]);
        $result = $this->searchService->findByRequest($request);

        $this->assertEquals('/en/search/make-am-general+balzum/location-netherlands+france/',$this->searchService->getSearchUrl('en'));
    }
}