<?php

namespace TradusBundle\Tests\Service\Search;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TradusBundle\Service\Search\Client;
use TradusBundle\Service\Search\Query;
use TradusBundle\Service\Search\QuerySuggest;
use TradusBundle\Service\Search\Request;
use TradusBundle\Service\Search\SearchService;

class ClientTest extends KernelTestCase {

    public function testSettingEndpoint() {
        $search = new Client();
        $this->assertEquals(null, $search->getEndPoint());

        $options = 'https://88.0.0.1:6666/solr3/tradus_dev/';
        $search = new Client($options);
        $endPoint = $search->getEndPoint();
        $this->assertNotEquals(null, $endPoint);
        $this->assertEquals('tradus_dev', $endPoint->getCore());
        $this->assertEquals('/solr3', $endPoint->getPath());
        $this->assertEquals('6666', $endPoint->getPort());
        $this->assertEquals('88.0.0.1', $endPoint->getHost());
        $this->assertEquals('https', $endPoint->getScheme());

        $this->assertEquals($options, $endPoint->getBaseUri());
    }

    public function testSearchQueryObject() {
        $search = new Client();
        $query = $search->getQuerySelect();
        $resultsPerPage = 6;

        // Test set number of results
        $query->setRows($resultsPerPage);
        $this->assertEquals($resultsPerPage, $query->getRows());

        // Test setting startOffset
        $startOffset = 10;
        $query->setStart($startOffset);
        $this->assertEquals($startOffset, $query->getStart());

        // Test Adding a sort
        $query->addSort('create_date', Query::SORT_DESC);
        $sorts = $query->getSorts();
        $this->assertEquals(true,isset($sorts['create_date']));
        $this->assertEquals(Query::SORT_DESC, $sorts['create_date']);

        // set fields to fetch (this overrides the default setting 'all fields')
        $query->setFields(array('id','thumbnail','price', 'score'));
        $fields = $query->getFields();
        $this->assertEquals(4, count($fields));

        // Test clearing the fields
        $query->clearFields();
        $this->assertEquals(0, count($query->getFields()));

        // Test setting a raw query
        $rawQuery = 'price:[50 TO *]';
        $query->setQuery($rawQuery);
        $this->assertEquals($rawQuery, $query->getQuery());

        // Test assembling query with values
        $rawQuery = 'category:%s AND seller_type:%s AND price:[%s TO *] AND !thumbnail:NULL';
        $expectedRawQuery = 'category:1 AND seller_type:1 AND price:[50 TO *] AND !thumbnail:NULL';
        $query->setQuery($rawQuery, [1,1,50]);
        $this->assertEquals($expectedRawQuery, $query->getQuery());

        // Test stacking setters
        $query->setStart(0)->setRows(20)->setQuery(Query::DEFAULT_QUERY)->addSort('sort_date', Query::SORT_DESC);
        $this->assertEquals(0, $query->getStart());
        $this->assertEquals(20, $query->getRows());
        $this->assertEquals(Query::DEFAULT_QUERY, $query->getQuery());
        $this->assertEquals(true,isset(($query->getSorts())['sort_date']));
        $this->assertEquals(Query::SORT_DESC,($query->getSorts())['sort_date']);

        // This create a range query
        $this->assertEquals('price:["50" TO *]', $query->rangeQuery('price', 50));
        $this->assertEquals('price:["10" TO "100"]', $query->rangeQuery('price', 10, 100));
        $this->assertEquals('price:[* TO "10"]', $query->rangeQuery('price', null, 10));
        $this->assertEquals('price:[* TO *]', $query->rangeQuery('price'));

        // Escape input
        $query->setQuery("query_field:". Query::escapePhrase("zoek voor mij iets"));
        $this->assertEquals('query_field:"zoek voor mij iets"', $query->getQuery());
    }

    public function testAddQuery() {
        $search = new Client();
        $query = $search->getQuerySelect();
        $query->addQuery('category', 1);
        $query->addQuery('category', 2);
        $query->addQuery('category', 2, Query::OPERATOR_OR);
        $this->assertEquals('category:1 AND category:2 OR category:2', $query->getQuery());

        $query->addRangeQuery('price', 50);
        $this->assertEquals('category:1 AND category:2 OR category:2 AND price:["50" TO *]', $query->getQuery());

        $query->setQuery('');
        $query->addQuery('category', [1,2,3,4,5]);
        $this->assertEquals('category:("1" OR "2" OR "3" OR "4" OR "5")', $query->getQuery());
    }

    public function testSearchRequest(){
        $search = new Client();
        $query = $search->getQuerySelect();
        $query->setStart(5)->setRows(8)->addSort('create_date');
        $request = $search->createRequest($query);
        $this->assertEquals("create_date desc", $request->getParam('sort'));
        $this->assertEquals("*,score", $request->getParam('fl'));
        $this->assertEquals(8, $request->getParam('rows'));
        $this->assertEquals(5, $request->getParam('start'));
        $this->assertEquals(Query::DEFAULT_QUERY, $request->getParam('q'));
        $this->assertEquals(Request::METHOD_GET, $request->getMethod());
        $this->assertEquals('select?q=%2A%3A%2A&start=5&rows=8&fl=%2A%2Cscore&ws=json&sort=create_date+desc',$request->getUri());
    }

    public function testSearchRequestWithFacets(){
        $search = new Client();
        $query = $search->getQuerySelect();
        $query->setStart(5)->setRows(8)->addSort('create_date');
        $query->enableFacet()->addFacetField('make');
        $request = $search->createRequest($query);
        $this->assertEquals("create_date desc", $request->getParam('sort'));
        $this->assertEquals("*,score", $request->getParam('fl'));
        $this->assertEquals(8, $request->getParam('rows'));
        $this->assertEquals(5, $request->getParam('start'));
        $this->assertEquals(Query::DEFAULT_QUERY, $request->getParam('q'));
        $this->assertEquals(Request::METHOD_GET, $request->getMethod());
        $this->assertEquals('select?q=%2A%3A%2A&start=5&rows=8&fl=%2A%2Cscore&ws=json&facet=on&facet.field=make&sort=create_date+desc',$request->getUri());

        $query->addFacetField('category');
        $request = $search->createRequest($query);
        $this->assertEquals('select?q=%2A%3A%2A&start=5&rows=8&fl=%2A%2Cscore&ws=json&facet=on&facet.field=make&facet.field=category&sort=create_date+desc',$request->getUri());
    }

    public function testRequestForSuggestions() {
        $search = new Client();
        $query = $search->getQuerySuggest();
        $query->setQuery("mercedes");
        $request = $search->createRequest($query);
        $this->assertEquals('mercedes', $request->getParam('suggest.q'));
        $this->assertEquals(QuerySuggest::DEFAULT_DICTIONARY, $request->getParam('suggest.dictionary'));
        $this->assertEquals('false', $request->getParam('suggest.build'));
        $this->assertEquals('suggest?suggest=true&suggest.build=false&suggest.dictionary=mySuggester&suggest.q=mercedes&wt=json', $request->getUri());
    }

    public function testSearchWithRelevance() {
        $search = new Client();
        $query = $search->getQuerySelect();
        $queryString = ['turbo','volvo'];
        $query->addRawQuery(SearchService::SEARCH_FIELDS_QUERY,$queryString, Query::OPERATOR_AND, Query::OPERATOR_AND);
        $query->enableEdismax()->addEdismaxBoostQuery(SearchService::SEARCH_FIELDS_TITLE, $queryString, 1.5);
        $query->enableEdismax()->addEdismaxBoostQuery(SearchService::SEARCH_FIELDS_MAKE, "make_string", 1.1);

        $request = $search->createRequest($query);
        $this->assertEquals('edismax',$request->getParam('defType'));
        $this->assertEquals('',$request->getParam('boost'));
        $this->assertEquals('title_en:("turbo" OR "volvo")^1.5 make:"make_string"^1.1',$request->getParam('bq'));
        $this->assertEquals('query:("turbo" AND "volvo")',$request->getParam('q'));

        $query->setEdismaxBoost('recip(ms(NOW/HOUR,sort_index),3.16e-11,1,0.5)');
        $request = $search->createRequest($query);
        $this->assertEquals('recip(ms(NOW/HOUR,sort_index),3.16e-11,1,0.5)',$request->getParam('boost'));



    }

/*
    public function testMakeResonseFile() {
        $search = new Client('http://127.0.0.1:8983/solr/tradus_dev');
        $query = $search->getQuerySelect();
        $query->addSort(SearchService::SEARCH_FIELDS_SORT_INDEX);
        $query->enableFacet()->setFacetFields([SearchService::SEARCH_FIELDS_MAKE, SearchService::SEARCH_FIELDS_CATEGORY,SearchService::SEARCH_FIELDS_COUNTRY]);
        $query->enableStats()->setStatsFields([SearchService::SEARCH_FIELDS_PRICE, SearchService::SEARCH_FIELDS_YEAR]);

        $result = $search->execute($query);
        $response = $result->getResponse();
        file_put_contents(__DIR__ . '/ResponseFiles/findByRequest.body', $response->getBody());
        var_dump($response->getBody());die;
    }
*/
}
