<?php

namespace TradusBundle\Service\Search;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Locale;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Seller;
use Symfony\Component\HttpFoundation\Request;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\MakeRepository;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Helper\OfferServiceHelper;
use Symfony\Component\Intl\Intl;

/**
 * Class SearchService
 *
 * @package TradusBundle\Service\Search
 */
class SearchService {

    const SEARCH_FIELDS_CREATE_DATE     = 'create_date';
    const SEARCH_FIELDS_SORT_INDEX      = 'sort_index';
    const SEARCH_FIELDS_CATEGORY        = 'category';
    const SEARCH_FIELDS_PRICE           = 'price';
    const SEARCH_FIELDS_THUMBNAIL       = 'thumbnail';
    const SEARCH_FIELDS_MAKE            = 'make';
    const SEARCH_FIELDS_SELLER_TYPE     = 'seller_type';
    const SEARCH_FIELDS_TITLE           = 'title_en';
    const SEARCH_FIELDS_QUERY           = 'query';
    const SEARCH_FIELDS_COUNTRY         = 'seller_country';
    const SEARCH_FIELDS_YEAR            = 'year';
    const SEARCH_SELLER_ID              = 'seller_id';
    const SEARCH_FIELDS_OFFER_ID        = 'offer_id';
    const SEARCH_FIELDS_IMAGE_COUNT     = 'images_count_facet_int';

    const REQUEST_FIELD_SORT            = 'sort';
    const REQUEST_FIELD_LIMIT           = 'limit';
    const REQUEST_FIELD_QUERY           = 'q';
    const REQUEST_FIELD_QUERY_FRONTEND  = 'query';
    const REQUEST_FIELD_PAGE            = 'page';
    const REQUEST_FIELD_MAKE            = 'make';
    const REQUEST_FIELD_COUNTRY         = 'country';
    const REQUEST_FIELD_CAT_L1          = 'cat_l1';
    const REQUEST_FIELD_CAT_L2          = 'cat_l2';
    const REQUEST_FIELD_CAT_L3          = 'cat_l3';
    const REQUEST_FIELD_PRICE_FROM      = 'price_from';
    const REQUEST_FIELD_PRICE_TO        = 'price_to';
    const REQUEST_FIELD_YEAR_FROM       = 'year_from';
    const REQUEST_FIELD_YEAR_TO         = 'year_to';
    const REQUEST_FIELD_SELLER          = 'seller_id';
    const REQUEST_FIELD_SELLER_SLUG     = 'seller_slug';
    const REQUEST_FIELD_DEBUG           = 'debug';
    const REQUEST_FIELD_OFFER           = 'offer_id';
    const REQUEST_FIELD_FROM_CREATE_DATE = 'create_date';
    const REQUEST_FIELD_SELLER_TYPES    = 'seller_types';
    const REQUEST_FIELD_HAS_IMAGE_COUNT = 'has_image_count';

    const REQUEST_VALUE_SORT_SORT_INDEX = "score-desc";
    const REQUEST_VALUE_SORT_RELEVANCY  = "relevancy";
    const REQUEST_VALUE_SORT_PRICE_ASC  = "price-asc";
    const REQUEST_VALUE_SORT_PRICE_DESC = "price-desc";
    const REQUEST_VALUE_SORT_TITLE_ASC  = "title-asc";
    const REQUEST_VALUE_SORT_TITLE_DESC = "title-desc";
    const REQUEST_VALUE_SORT_DATE_ASC   = "date-asc";
    const REQUEST_VALUE_SORT_DATE_DESC  = "date-desc";

    const REQUEST_VALUE_DEFAULT_PAGE    = 1;
    const REQUEST_VALUE_DEFAULT_SORT    = self::REQUEST_VALUE_SORT_RELEVANCY;
    const REQUEST_VALUE_DEFAULT_LIMIT   = Query::DEFAULT_ROWS;
    const REQUEST_VALUE_MAX_LIMIT       = 100;

    const DELIMITER_MULTI_VALUE         = '+';
    const DELIMITER_QUERY_TEXT          = ' ';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Result
     */
    protected $result;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $params = [];


    /**
     * Boost offers with images
     * @var float
     */
    protected $relevancyBoostHasImageScore;

    /**
     * Boost offers with a proper price
     * @var float
     */
    protected $relevancyBoostPriceScore;

    /**
     * Boost all sellers except free
     * @var float
     */
    protected $relevancyBoostSellerTypesScore;
    /**
     * Used to boost the words appearing in title
     * Higher value scores documents higher with the words in the title
     * @var float
     */
    protected $relevancyBoostTitleScore;

    /**
     * Boost country score
     * @var float
     */
    protected $relevancyBoostCountryScore;

    /**
     * List of (sellers) countries to boost in search
     * @var array
     */
    protected $relevancyBoostCountryList;

    /**
     * Used to reference the age of the offer in sort-index sorting by score
     * @var string
     */
    protected $relevancyTimeBoostReferenceTime = '6.33e-11'; // 6 months

    /**
     * Used to calculate sort-index into a score
     * @var float
     */
    protected $relevancyBoostTimeA;

    /**
     * Used to calculate sort-index into a score
     * Lower to give older documents less value
     * @var float
     */
    protected $relevancyBoostTimeB;

    /**
     * Enable/Disable Solr debug output.
     * @var bool
     */
    protected $searchDebug = false;

    /**
     * @var EntityManagerInterface
     */
    protected $entity_manager;

    /**
     * SearchService constructor.
     *
     * @param null $options
     * @param EntityManagerInterface $entityManager
     */
    public function __construct($options = null, EntityManagerInterface $entityManager) {
        $endpoint = null;
        if ($options && isset($options['endpoint']))
            $endpoint = $options['endpoint'];

        $this->setClient(new Client($endpoint));
        $this->entity_manager = $entityManager;
        $this->loadConfiguration();
    }

    /**
     * Loads configuration from database
     */
    protected function loadConfiguration() {
        global $kernel;
        /* @var ConfigService $config */
        $config = $kernel->getContainer()->get('tradus.config');
        $this->relevancyBoostHasImageScore    = $config->getSettingValue('relevancy.boostHasImageScore');
        $this->relevancyBoostPriceScore       = $config->getSettingValue('relevancy.boostPriceScore');
        $this->relevancyBoostSellerTypesScore = $config->getSettingValue('relevancy.boostSellerTypesScore');
        $this->relevancyBoostTitleScore       = $config->getSettingValue('relevancy.boostTitleScore');
        $this->relevancyBoostCountryScore     = $config->getSettingValue('relevancy.boostCountryScore');
        $this->relevancyBoostCountryList      = $config->getSettingValue('relevancy.boostCountryList');
        $this->relevancyBoostTimeA            = $config->getSettingValue('relevancy.boostTimeA');
        $this->relevancyBoostTimeB            = $config->getSettingValue('relevancy.boostTimeB');
    }

    /**
     * @return Query
     */
    public function getQuerySelect() {
        return $this->client->getQuerySelect();
    }

    /**
     * @param Query $query
     * @return Result
     */
    public function execute(Query $query = null) {
        if ($query)
            $this->query = $query;

        $this->result = $this->client->execute($this->query);
        return $this->result;
    }

    /**
     * Get Autocomple suggestions
     *
     * @param string $query
     * @param bool $rebuildSuggestionDictionary
     * @return Result
     */
    public function getAutoCompleteSuggestions(string $query, $rebuildSuggestionDictionary = false) {
        $this->query = $this->client->getQuerySuggest();
        $this->query->setQuery($query);
        $this->query->setBuild($rebuildSuggestionDictionary);
        $this->result = $this->client->execute($this->query);

        return $this->result;
    }

    /**
     * @param int $categoryId
     * @param int $minPrice
     * @return array
     */
    public function findLatestPremiumOffersBy(int $categoryId, $minPrice = 1000){
        $this->query = $this->client->getQuerySelect();

        $this->query->setRows(100)
            ->addSort(self::SEARCH_FIELDS_CREATE_DATE)
            ->addQuery(self::SEARCH_FIELDS_CATEGORY,  $categoryId)
            ->addRangeQuery(self::SEARCH_FIELDS_PRICE, $minPrice)
            ->addRangeQuery(self::SEARCH_FIELDS_IMAGE_COUNT, 1)
            ->addQuery(self::SEARCH_FIELDS_SELLER_TYPE, [
                Seller::SELLER_TYPE_PREMIUM,
                Seller::SELLER_TYPE_PACKAGE_GOLD,
                Seller::SELLER_TYPE_PACKAGE_SILVER,
                Seller::SELLER_TYPE_PACKAGE_BRONZE,
            ]);
        $this->result = $this->client->execute($this->query);
        // We only need 6 and we prefer some results above (boost sellers)
        $this->result->shuffleDocuments()->boostSellerTypesDocuments(false)->limitDocuments(6);
        return $this->getTradusResult($this->result);
    }

    /**
     * Finds Similar offers based on a category and make
     *
     * @param int $categoryId
     * @param string $makeName
     * @param int|bool $excludeOfferId
     * @param bool $imageCountFilter
     * @return array
     */
    public function findSimilarOffersBy(int $categoryId, string $makeName, $excludeOfferId = false, $title = false) {

        $this->query = $this->client->getQuerySelect();

        $this->query->addSort(self::SEARCH_FIELDS_SORT_INDEX)
            ->setRows(100)
            ->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId)
            ->addQuery(self::SEARCH_FIELDS_MAKE, $makeName);

        $this->result = $this->client->execute($this->query);


        // Filter the free sellers out of the result set, but keep in the total count/numFound
        $filterSellerType = false;
        if ($this->result->getNumberFound() > 6) {
            $filterSellerType = true;

            // Don't show same listing
            if ($excludeOfferId)
                $this->result->filterDocuments(self::SEARCH_FIELDS_OFFER_ID, $excludeOfferId);

            // Remove offers without images
            $this->result->filterDocuments(self::SEARCH_FIELDS_IMAGE_COUNT, 0);

            if (!$title)
                $this->result->shuffleDocuments()->boostSellerTypesDocuments($filterSellerType)->limitDocuments(6);
            else
                $this->result->orderBySimilarity($title)->limitDocuments(30)->boostSellerTypesDocuments($filterSellerType)->limitDocuments(6);
        } else {
            /*
             * This will do a MoreLikeThis search based on offerid, will be more accurated, but can only return offers, no faccets etc.
             */
            $this->query->enableMlt();
            $this->query->addQuery(self::SEARCH_FIELDS_OFFER_ID, $excludeOfferId);
            $this->result = $this->client->execute($this->query);
            $this->result->replaceDocumentsWithMoreLikeThis($excludeOfferId)->limitDocuments(6);
        }
        return $this->getTradusResult($this->result);
    }

    /**
     * Get sidewide search Facetdata
     *
     * @return Result
     */
    public function getCategoryFacetDataSideWide() {
        $this->query =  $this->client->getQuerySelect();
        $this->query->setRows(0)->addSort(self::SEARCH_FIELDS_SORT_INDEX);
        $this->query->enableFacet()->addFacetField(self::SEARCH_FIELDS_CATEGORY);
        $this->query->setFacetLimit(self::SEARCH_FIELDS_CATEGORY,200);
        $this->result = $this->client->execute($this->query);

        return $this->result;
    }

    /**
     * @param Request $request
     * @return Result
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByRequest(Request $request) {
        $query = $this->client->getQuerySelect();
        $this->query = $this->createQueryFromRequest($query, $request);
        return $this->execute($this->query);
    }

    /**
     * @param Query $query
     * @param Request $request
     * @return Query
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createQueryFromRequest(Query $query, Request $request) {
        $this->resetParams();
        $this->query   = $query;
        $this->request = $request;

        /**
         * Enable/Disble debug information
         */
        if ($this->searchDebug === true || $this->requestHas(self::REQUEST_FIELD_DEBUG)) {
            $this->query->enableDebug();
        }

        /**
         * Set the sorting
         */
        $searchSort = self::REQUEST_VALUE_DEFAULT_SORT;
        if ($this->requestHas(self::REQUEST_FIELD_SORT)) {
            $searchSort = $this->requestGet(self::REQUEST_FIELD_SORT);
            // TODO: remove the following when it's removed from front-end
            if ($searchSort == "sort_index-desc") {
                $searchSort = self::REQUEST_VALUE_SORT_RELEVANCY;
                $this->params[self::REQUEST_FIELD_SORT] = $searchSort;
            }
        }
        $this->setQuerySort($searchSort);

        /**
         * What is the LIMIT of number of results
         */
        $searchLimit = self::REQUEST_VALUE_DEFAULT_LIMIT;
        if ($this->requestHas(self::REQUEST_FIELD_LIMIT)) {
            $searchLimit = $this->requestGet(self::REQUEST_FIELD_LIMIT);

            // Limit the maximum results to retrieve for performance.
            if ($searchLimit > self::REQUEST_VALUE_MAX_LIMIT)
                $searchLimit = self::REQUEST_VALUE_MAX_LIMIT;

            $this->query->setRows($searchLimit);
        }

        /**
         * Add the PAGE NUMBER to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_PAGE) && $this->requestGet(self::REQUEST_FIELD_PAGE) > self::REQUEST_VALUE_DEFAULT_PAGE) {
            $start = ($this->requestGet(self::REQUEST_FIELD_PAGE) - 1) * $searchLimit;
            $this->query->setStart($start);
        }

        /**
         * Add CATEGORY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1))
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L1));

        /**
         * Add CATEGORY TYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2))
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L2));

        /**
         * Add CATEGORY SUBTYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3))
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L3));

        /**
         * Add CATEGORY SUBTYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_HAS_IMAGE_COUNT))
            $this->query->addRangeQuery(self::SEARCH_FIELDS_IMAGE_COUNT, $this->requestGet(self::REQUEST_FIELD_HAS_IMAGE_COUNT));


        /**
         * Add offer id to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_OFFER))
            $this->query->addRawQuery(self::REQUEST_FIELD_OFFER, $this->requestGet(self::REQUEST_FIELD_OFFER), Query::OPERATOR_AND, Query::OPERATOR_SPACE);

        /**
         * Add QUERY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawQuery(self::SEARCH_FIELDS_QUERY, $this->getParsedSearchQuery(), Query::OPERATOR_AND, Query::OPERATOR_AND);
        }

        /**
         * Add create date filter to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_FROM_CREATE_DATE)) {
            $this->query->addRangeQuery(SearchService::SEARCH_FIELDS_CREATE_DATE, $this->requestGet(self::REQUEST_FIELD_FROM_CREATE_DATE));
        }

        /**
         * Add Attribute MAKE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            $makesRequest = $this->requestGet(self::REQUEST_FIELD_MAKE);
            $searchMakes = $this->getMakeValuesAsIndexed($makesRequest);
            $this->query->addQuery(self::SEARCH_FIELDS_MAKE, $searchMakes);
        }

        /**
         * Add Attribute COUNTRY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            if (!is_array($this->requestGet(self::REQUEST_FIELD_COUNTRY)))
                $country = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
            else
                $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            $this->query->addQuery(self::SEARCH_FIELDS_COUNTRY, $country);
        }

        /**
         * Add filter on seller types
         */
        if ($this->requestHas(self::REQUEST_FIELD_SELLER_TYPES)) {
            $this->query->addQuery(self::SEARCH_FIELDS_SELLER_TYPE, $this->requestGet(self::REQUEST_FIELD_SELLER_TYPES));
        }

        /**
         * Add PRICE FILTER to the search
         */
        if($this->requestHas(self::REQUEST_FIELD_PRICE_FROM) || $this->requestHas(self::REQUEST_FIELD_PRICE_TO))
            $this->query->addRangeQuery(self::SEARCH_FIELDS_PRICE, $this->requestGet(self::REQUEST_FIELD_PRICE_FROM), $this->requestGet(self::REQUEST_FIELD_PRICE_TO));

        /**
         * Add YEAR Filter to the search
         */
        if($this->requestHas(self::REQUEST_FIELD_YEAR_FROM) || $this->requestHas(self::REQUEST_FIELD_YEAR_TO))
            $this->query->addRangeQuery(self::SEARCH_FIELDS_YEAR, $this->requestGet(self::REQUEST_FIELD_YEAR_FROM), $this->requestGet(self::REQUEST_FIELD_YEAR_TO));

        /**
         * SELLER PAGE Search
         */
        if ($this->requestHas(self::REQUEST_FIELD_SELLER))
            $this->query->addQuery(self::SEARCH_SELLER_ID, $this->requestGet(self::REQUEST_FIELD_SELLER));

        /**
         * Enable facet data retrieval in search
         */
        $this->query->enableFacet()->setFacetFields([self::SEARCH_FIELDS_MAKE, self::SEARCH_FIELDS_CATEGORY,self::SEARCH_FIELDS_COUNTRY]);

        /**
         * Enable stats data retrieval in search
         */
        $this->query->enableStats()->setStatsFields([self::SEARCH_FIELDS_PRICE, self::SEARCH_FIELDS_YEAR]);

        return $this->query;
    }

    /**
     * TODO: Refactoring this logic to FE
     * Make is indexed as Make Name, and we need to search as exact match
     * This function transform slugs into these make names.
     *
     * @param $slugValues
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMakeValuesAsIndexed($slugValues) {
        /* @var MakeRepository $makesRepo */
        $makesRepo = $this->entity_manager->getRepository('TradusBundle:Make');

        if (!is_array($slugValues))
            $slugValues = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_MAKE));

        // TODO: Filter on numeric make request (transfer in make slug), this should not be happening, but somewhere it did  in Frond-end request or old platform
        foreach($slugValues as $key => $makeValue) {
            if (is_numeric($makeValue)) {
                /* @var Make $make */
                $make = $makesRepo->getMakeById($makeValue);
                $slugValues[$key] = $make->getSlug();
                // Also fix the Request Object
                if (isset($this->request) && $this->request->query->has(self::REQUEST_FIELD_MAKE) && is_string($this->request->query->get(self::REQUEST_FIELD_MAKE))) {
                    $newRequestValue = str_replace($makeValue, $make->getSlug(),  $this->request->query->get(self::REQUEST_FIELD_MAKE));
                    $this->request->query->set(self::REQUEST_FIELD_MAKE, $newRequestValue);
                }
            }
        }

        // Makes are indexed as Exact Match
        $makes = $makesRepo->getMakesBySlug($slugValues);
        $searchMakes = [];
        if ($makes) {
            foreach($makes as $make) {
                /* @var Make $make */
                array_push($searchMakes, $make->getName());
            }
        }
        return $searchMakes;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @return array|mixed
     */
    public function getParsedSearchQuery() {
        $searchQuery = $this->requestGet(self::REQUEST_FIELD_QUERY);
        $searchQuery = str_replace('-',self::DELIMITER_QUERY_TEXT, $searchQuery);
        if (!is_array($searchQuery))
            $searchQuery = explode(self::DELIMITER_QUERY_TEXT, $searchQuery);
        return $searchQuery;
    }

    /**
     * Set sorting based on Sort String
     * @param String $sort
     */
    public function setQuerySort(String $sort) {
        switch ($sort) {
            case self::REQUEST_VALUE_SORT_PRICE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_PRICE, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_PRICE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_PRICE, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_TITLE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_TITLE, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_TITLE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_TITLE, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_DATE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_CREATE_DATE, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_DATE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_CREATE_DATE, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_RELEVANCY:
            case self::REQUEST_VALUE_SORT_SORT_INDEX:
            default:
                $this->setRelevancy();
                break;
        }
    }

    /**
     * Set solr sorting on score and converts the sort_index age into a score
     */
    public function setRelevancy() {
        // Enable Relevancy with Edismax
        $this->query->enableEdismax();

        // Sort on score
        $this->query->addSort('score', Query::SORT_DESC);

        /**
         * Boost keywords in title
         */
        if ($this->relevancyBoostTitleScore > 0 && $this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_TITLE, $this->getParsedSearchQuery(), $this->relevancyBoostTitleScore, Query::OPERATOR_AND);
        }

        /**
         * Default Boost seller types excluding free sellers (so free sellers get lower in the results)
         */
        if ($this->relevancyBoostSellerTypesScore > 0) {
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_SELLER_TYPE, "[1 TO *]", $this->relevancyBoostSellerTypesScore);
        }

        /**
         * Boost offers with a price above 100 to 1000 and bigger boost for >1000
         */
        if ($this->relevancyBoostPriceScore > 0) {
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_PRICE, "[1000.0 TO *]", $this->relevancyBoostPriceScore);
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_PRICE, "[100.0 TO 999.0]", ($this->relevancyBoostPriceScore / 2));
        }

        /**
         * Boost offers with images
         */
        if ($this->relevancyBoostHasImageScore > 0) {
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_IMAGE_COUNT, "[1 TO *]", $this->relevancyBoostHasImageScore);
        }

        /**
         * Boost offers with seller country
         */
        if ($this->relevancyBoostCountryScore > 0 && count($this->relevancyBoostCountryList)) {
            $this->query->addRawEdismaxBoostQuery(self::SEARCH_FIELDS_COUNTRY, $this->relevancyBoostCountryList, $this->relevancyBoostCountryScore);
        }

        /**
         * Boost newer offers (based on sort-index)
         */
        $referenceTime  = $this->relevancyTimeBoostReferenceTime;
        $multiplierA    = $this->relevancyBoostTimeA;
        $multiplierB    = $this->relevancyBoostTimeB;
        // With above settings we get a very linear decrease of score based on age (sort-index) of the offer.
        // Lower B to for example 0.8 to decrease score quicker with aging of the offer.

        // We use NOW/HOUR+1HOUR to stabilize the result set for one hour.
        $this->query->setEdismaxBoost('recip(ms(NOW/HOUR+1HOUR,'.self::SEARCH_FIELDS_SORT_INDEX.'),'.$referenceTime.','.$multiplierA.','.$multiplierB.')');
        /*
         * Explanation:
         * recip(x, m, a, b) implements f(x) = a/(xm+b) with :
            x : the document age in ms, defined as ms(NOW,<datefield>).
            m : a constant that defines a time scale which is used to apply boost. It should be relative to what you consider an old document age (a reference_time) in milliseconds. For example, choosing a reference_time of 1 year (3.16e10ms) implies to use its inverse : 3.16e-11 (1/3.16e10 rounded).
            a and b are constants (defined arbitrarily).
            xm = 1 when the document is 1 reference_time old (multiplier = a/(1+b)).
            xm ≈ 0 when the document is new, resulting in a value close to a/b.
            Using the same value for a and b ensures the multiplier doesn't exceed 1 with recent documents.
            With a = b = 1, a 1 reference_time old document has a multiplier of about 1/2, a 2 reference_time old document has a multiplier of about 1/3, and so on.

            Make Boosting Stronger:
            Increase m : choose a lower reference_time for example 6 months, that gives us m = 6.33e-11. Comparing to a 1 year reference, the multiplier decreases 2x faster as the document age increases.
            Decreasing a and b expands the response curve of the function. This can be very agressive.
        */
    }

    /**
     * TODO: Refactoring this logic to FE
     * @param string $locale
     * @return string
     */
    public function getSearchBaseUrl($locale = 'en') {
        // BASE PATH
        $basePath = 'search';
        if($this->requestHas(self::REQUEST_FIELD_SELLER_SLUG)) {
            $basePath = 's/'.$this->requestGet(self::REQUEST_FIELD_SELLER_SLUG);
        }
        return "/{$locale}/{$basePath}/";
    }

    /**
     * TODO: Refactoring this logic to FE
     * Gets the full search url with params
     *
     * @param string $locale
     * @return string
     */
    public function getSearchUrlFull($locale = 'en') {
        $searchUrl = $this->getSearchUrl($locale);
        $searchParams = $this->getSearchUrlParametersString();
        if ($searchParams)
            $searchUrl = $searchUrl .'?'.$searchParams;
        return $searchUrl;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @param string $locale
     * @param Integer|bool $categoryId
     * @return string
     */
    public function getSearchUrl($locale = 'en', $categoryId = false) {
        $slugify = new Slugify();
        $categoryRepo = $this->entity_manager->getRepository("TradusBundle:Category");

        $basePath = $this->getSearchBaseUrl($locale);

        // QUERY PATH
        $queryPath = "";
        if ($this->requestHas(self::REQUEST_FIELD_QUERY) && !empty($this->requestGet(self::REQUEST_FIELD_QUERY))) {
            $queryPath = 'q/' . strtolower($slugify->slugify($this->requestGet(self::REQUEST_FIELD_QUERY) )). '/';
        }

        // CATEGORY PATH
        $categoryPath = "";
        if (!$categoryId) {
            if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L3);
            } elseif ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
            } elseif ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
            }
        }
        if ($categoryId !== false) {
            $category = $categoryRepo->find($categoryId);
            if ($category)
                $categoryPath = $category->getSearchSlugUrl($locale);
        }

        // MAKE PATH
        $makePath = "";
        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            $makePath = OfferServiceHelper::localizedMake($locale) . $this->requestGet(self::REQUEST_FIELD_MAKE) . '/';
        }

        // COUNTRY PATH
        $countryPath = "";
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            if (!is_array($country))
                $country = explode(self::DELIMITER_MULTI_VALUE, $country);
            $countries_search = [];

            \Locale::setDefault($locale);
            $countries = Intl::getRegionBundle()->getCountryNames();

            foreach ($country as $shortCode) {
                if ($shortCode == "EN")
                    continue;
                if ($shortCode == "DA") $shortCode = 'DK'; // Fix Denmark

                if (isset($countries[$shortCode]))
                    $countries_search[] = strtolower($slugify->slugify($countries[$shortCode]));
            }
            if (count($countries_search) >= 1) {
                $location_str = implode('+', $countries_search);
                $countryPath = OfferServiceHelper::localizedLocation($locale) . $location_str . '/';
            }
        }

        $searchUrl = "{$basePath}{$queryPath}{$categoryPath}{$makePath}{$countryPath}";
        return $searchUrl;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @param array $excludeParams
     * @return string
     */
    public function getSearchUrlParametersString($excludeParams = array()) {
        $parameterForInUrl = [
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY, // FRONT_END USES ANOTHER FIELD
            self::REQUEST_FIELD_SORT,
            self::REQUEST_FIELD_YEAR_FROM,
            self::REQUEST_FIELD_YEAR_TO,
            self::REQUEST_FIELD_PRICE_FROM,
            self::REQUEST_FIELD_PRICE_TO,
        ];

        $urlParameters = [];
        foreach ($parameterForInUrl as $parameterName) {
            $parameterValue = $this->getParam($parameterName);
            if ($parameterValue && !in_array($parameterName, $excludeParams)) {
                // Exclude defaults
                if(self::REQUEST_FIELD_PAGE == $parameterName && $parameterValue == self::REQUEST_VALUE_DEFAULT_PAGE)
                    continue;

                if(self::REQUEST_FIELD_SORT == $parameterName && $parameterValue == self::REQUEST_VALUE_DEFAULT_SORT)
                    continue;

                if ($parameterName == self::REQUEST_FIELD_QUERY) {
                    $parameterName = self::REQUEST_FIELD_QUERY_FRONTEND; // FE uses another parameter
                }

                if (!empty($parameterValue) || $parameterValue === 0)
                    $urlParameters[$parameterName] = $parameterValue;
            }
        }

        return http_build_query($urlParameters);
    }

    /**
     * TODO: Refactoring this logic to FE
     * @param string $locale
     * @param string $cat
     * @return array
     */
    public function getFacetCategoriesData($locale = 'en', $cat = 'l1') {
        /* @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');

        if ($cat == 'l1') {
            $categories = $categoryRepo->getL1Categories();
        } elseif ($cat == 'l2') {
            $categories = $categoryRepo->getL2Categories();
        } elseif ($cat == 'l3') {
            $categories = $categoryRepo->getL3Categories();
        }

        $result = [];
        if ($categories) {
            foreach ($categories as $category) {
                $urlParameters = $this->getSearchUrlParametersString([SearchService::REQUEST_FIELD_PAGE, SearchService::REQUEST_FIELD_QUERY]);
                if ($urlParameters) {
                    $urlParameters = '?' . $urlParameters;
                }

                /* @var Category $category */
                $ret['id'] = $category->getId();
                $ret['name'] = $category->getNameTranslation($locale);
                $ret['search_url'] = $this->getSearchUrl($locale, $category->getId()) . $urlParameters;

                $parentCategory = $category->getParent();
                if ($parentCategory)
                    $ret['reset_link'] = $this->getSearchUrl($locale, $parentCategory->getId()) . $urlParameters;
                else
                    $ret['reset_link'] = $this->getSearchUrl($locale, 99999999) . $urlParameters;

                $result[$category->getId()] = $ret;
            }
        }

        return $result;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @return array
     */
    public function getFacetMakesData() {
        $makesRepo = $this->entity_manager->getRepository('TradusBundle:Make');
        $makes = $makesRepo->findAll();
        $facetLookup = [];
        /* @var Make $make */
        foreach($makes as $make) {
            $facetLookup[$make->getName()] = [
                'name' => $make->getName(),
                'id' => $make->getSlug(),
                'search_url' => null,
                'reset_link' => null,
            ];
        }
        return $facetLookup;
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getFacetCountriesData($locale = 'en') {
        \Locale::setDefault($locale);
        $countries = Intl::getRegionBundle()->getCountryNames();
        $facetLookup = [];
        foreach($countries as $shortCode => $country) {
            $facetLookup[$shortCode] = [
                'name' => $country,
                'id' => $shortCode,
                'search_url' => null,
                'reset_link' => null,
            ];
        }
        return $facetLookup;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @return array
     */
    public function getResultSorts() {
        $sort = $this->getParam(self::REQUEST_FIELD_SORT);
        if (empty($sort)) {
            $sort = self::REQUEST_VALUE_DEFAULT_SORT;
        }
        $urlParameters = $this->getSearchUrlParametersString([SearchService::REQUEST_FIELD_SORT, SearchService::REQUEST_FIELD_PAGE]);
        if ($urlParameters) {
            $urlParameters = '&'.$urlParameters;
        }
        $result = [
            self::REQUEST_VALUE_SORT_PRICE_ASC => [
                "label" => "Price ↑",
                "selected" => $sort == self::REQUEST_VALUE_SORT_PRICE_ASC ?: false,
                "value" => self::REQUEST_VALUE_SORT_PRICE_ASC.$urlParameters,
            ],
            self::REQUEST_VALUE_SORT_PRICE_DESC => [
                "label" => "Price ↓",
                "selected" => $sort == self::REQUEST_VALUE_SORT_PRICE_DESC ?: false,
                "value" => self::REQUEST_VALUE_SORT_PRICE_DESC.$urlParameters,
            ],
            /** TODO: CAN BE REMOVED WHEN NOT USED ANYMORE
            self::REQUEST_VALUE_SORT_SORT_INDEX => [
            "label" => "Relevance",
            "selected" => (empty($sort) || $sort == self::REQUEST_VALUE_SORT_SORT_INDEX)?: false,
            "value" => self::REQUEST_VALUE_SORT_SORT_INDEX.$urlParameters,
            ],
             */
            self::REQUEST_VALUE_SORT_RELEVANCY => [
                "label" => "Relevance",
                "selected" => $sort == self::REQUEST_VALUE_SORT_RELEVANCY ?: false,
                "value" => self::REQUEST_VALUE_SORT_RELEVANCY.$urlParameters,
            ],
            self::REQUEST_VALUE_SORT_TITLE_ASC => [
                "label" => "Title ↑",
                "selected" => $sort == self::REQUEST_VALUE_SORT_TITLE_ASC ?: false,
                "value" =>  self::REQUEST_VALUE_SORT_TITLE_ASC.$urlParameters,
            ],
            self::REQUEST_VALUE_SORT_TITLE_DESC => [
                "label" => "Title ↓",
                "selected" => $sort == self::REQUEST_VALUE_SORT_TITLE_DESC ?: false,
                "value" => self::REQUEST_VALUE_SORT_TITLE_DESC.$urlParameters,
            ],
            self::REQUEST_VALUE_SORT_DATE_DESC => [
                "label" => "Date",
                "selected" => $sort == self::REQUEST_VALUE_SORT_DATE_DESC ?: false,
                "value" => self::REQUEST_VALUE_SORT_DATE_DESC.$urlParameters,
            ],
        ];
        return $result;
    }

    /**
     * TODO: Refactoring this logic to FE
     * @param string $locale
     * @return string
     */
    public function getSearchQueryText($locale = 'en') {
        $slugify = new Slugify();
        $categoryRepo = $this->entity_manager->getRepository("TradusBundle:Category");
        $searchQueryText = '';

        if ($this->requestHas(self::REQUEST_FIELD_QUERY))
            $searchQueryText .= $this->requestGet(self::REQUEST_FIELD_QUERY);

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            /* @var Category $category */
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L1)))) {
                if ($searchQueryText != '')  $searchQueryText .= ', ';
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            /* @var Category $category */
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L2)))) {
                if ($searchQueryText != '')  $searchQueryText .= ', ';
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            /* @var Category $category */
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L3)))) {
                if ($searchQueryText != '')  $searchQueryText .= ', ';
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }

        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            if ($searchQueryText != '')  $searchQueryText .= ', ';
            $makes = explode('+', $this->requestGet(self::REQUEST_FIELD_MAKE));
            foreach ($makes as $make) {
                $searchQueryText .= ucfirst($make) . ', ';
            }
            $searchQueryText = trim($searchQueryText);
            $searchQueryText = rtrim($searchQueryText, ',');
        }

        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $countries_list = Intl::getRegionBundle()->getCountryNames($locale);
            foreach ($this->requestGet(self::REQUEST_FIELD_COUNTRY) as $country) {
                if ($searchQueryText != '')  $searchQueryText .= ', ';
                $searchQueryText .= $countries_list[$country];
            }
        }

        return $searchQueryText;
    }

    /**
     * Get the official result object
     *
     * @return Result
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * TODO: Remove when migration is done
     * Make the result compatible with current application expects
     *
     * @param Result $result
     * @return array
     */
    public function getTradusResult(Result $result) {
        $data['result_count'] = $result->getNumberFound();

        if (count($result->getDocuments())) {
            foreach ($result->getDocuments() as $doc) {
                $data['offer_ids'][] = $doc['offer_id'];
            }
        }
        if (isset($data['facet_counts']))
            $data['facet'] = $data['facet_counts'];

        $data =  array_merge($data, $result->getData());

        return $data;
    }

    /**
     * Will find facet values for current search without query on given field
     *
     * @param $facetValues
     * @param string $field
     * @return mixed
     */
    public function mergeFacetValuesForNotSelectedValues($facetValues, string $field) {
        $query = $this->query;
        $query->setRows(0)->disableStats()->clearStatsFields()->clearFacetFields()->addFacetFields($field);
        $query->replaceRawQueryField($field, '*');

        $searchResult = $this->client->execute($query);

        $additionalFacetData = $searchResult->getFacetFields();
        if (isset($additionalFacetData[$field]) && count($additionalFacetData[$field])) {
            // Do a foreach because array_merge reorders the results to alphabetic
            foreach($additionalFacetData[$field] as $key => $value) {
                if (isset($facetValues[$key]) && $facetValues[$key] == 0)
                    unset($facetValues[$key]);
                $facetValues[$key] = $value;
            }
        }
        return $facetValues;
    }

    /**
     * TODO: This logic needs to become more dynamic, easier to add new attributes
     * @return array
     */
    public function getFacetDataResults() {
        $result = [];

        $locale = $this->request->query->get('locale') ?: 'en';

        // Get range data
        $statsFields = $this->result->getStatsFields();
        if (!empty($statsFields) && is_array($statsFields)) {
            foreach($statsFields as $fieldName => $fieldValues) {
                $result[$fieldName] = $this->getRangeFacetData(
                    $fieldName,
                    $this->requestGet($fieldName.'_from'),
                    $this->requestGet($fieldName.'_to'),
                    (int) $fieldValues['min'],
                    (int) $fieldValues['max']
                );
            }
        }

        $facetFields = $this->result->getFacetFields();
        foreach( $facetFields as $facetName => $facetValues) {
            if($facetName == self::SEARCH_FIELDS_MAKE) {
                $facetLookup = $this->getFacetMakesData();

                // Get the additional values if the search was done without  make
                if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues($facetValues, self::SEARCH_FIELDS_MAKE);
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_MAKE);
                $result[$facetName] = $this->transformFacetData($facetName, $selected, $facetLookup, $facetValues);
            }

            if ($facetName == self::SEARCH_FIELDS_COUNTRY) {
                $facetName = self::REQUEST_FIELD_COUNTRY;
                $facetLookup = $this->getFacetCountriesData($locale);

                // Get the additional values if the search was done without  country
                if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues($facetValues, self::SEARCH_FIELDS_COUNTRY);
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
                $result[$facetName] = $this->transformFacetData($facetName, $selected, $facetLookup, $facetValues);
            }

            if ($facetName == 'category') {
                $selected1 = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
                $facetLookup = $this->getFacetCategoriesData($locale,  'l1');
                $result[$facetName] = $this->transformFacetData($facetName, $selected1, $facetLookup, $facetValues);

                $facetName = 'type';
                $result[$facetName] = [];
                if(!empty($selected1)) {
                    $selected2 = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
                    $facetLookup = $this->getFacetCategoriesData($locale,  'l2');
                    $result[$facetName] = $this->transformFacetData($facetName, $selected2, $facetLookup, $facetValues);
                }

                $facetName = 'subtype';
                $result[$facetName] = [];
                if(!empty($selected2)) {
                    $selected3 = $this->requestGet(self::REQUEST_FIELD_CAT_L3);
                    $facetLookup = $this->getFacetCategoriesData($locale,  'l3');
                    $result[$facetName] = $this->transformFacetData($facetName, $selected3, $facetLookup, $facetValues);
                }
            }
        }

        return $result;
    }

    public function getRangeFacetData($facetName, $minValue, $maxValue, $minLimit = 0, $maxLimit = 6250000, $step = 1) {
        $result['name'] = $facetName;
        $result['label'] = $facetName;
        $result['values']['min'] = $minValue;
        $result['values']['max'] = $maxValue;
        $result['values']['step'] = $step;
        $result['limits']['min'] = $minLimit;
        $result['limits']['max'] = $maxLimit;

        // TODO: ADD REAL EXCHANGE RATES
        if ($facetName == self::SEARCH_FIELDS_PRICE) {
            $result['rates'] = '{\"CHF\":1.1745180404280502,\"DKK\":7.446655567620915,\"EUR\":1,\"GBP\":0.8823802445128737,\"HUF\":308.344058610779,\"PLN\":4.166405069568542,\"RON\":4.651520907206521,\"RUB\":69.24888449704345,\"SEK\":9.82306345877372,\"TRY\":4.644444960174132,\"UAH\":35.098798200537686,\"USD\":1.221465049609803}';
        }
        if ($facetName == self::SEARCH_FIELDS_YEAR) {
            $maxYear = (new \DateTime())->format("Y")+1;
            if ($maxLimit > $maxYear)
                $result['limits']['max'] = $maxYear;
        }
        return $result;
    }

    /**
     * TODO: This logic should move to FE
     * @param $facetName
     * @param $selected
     * @param $facetLookup
     * @param $facetValues
     * @return array
     */
    public function transformFacetData($facetName, $selected, $facetLookup, $facetValues) {
        if (!is_array($selected)) {
            $selected = explode(self::DELIMITER_MULTI_VALUE, $selected);
        }

        $result = [
            'name' => $facetName,
            'selectedOption' => '',
            'items' => [],
        ];

        foreach ($facetValues as $facetId => $facetCount) {
            if (isset($facetLookup[$facetId]) && $facetCount > 0) {

                $value = $facetLookup[$facetId]['id'];

                $item = [
                    'label' => $facetLookup[$facetId]['name'],
                    'value' => $value,
                    'id'    => $facetId,
                    'url' => $facetLookup[$facetId]['search_url'],
                    'resetLink' => @$facetLookup[$facetId]['reset_link'],
                    'resultCount' => $facetCount,
                    'checked' => false,
                ];

                foreach ($selected as $selectedValue) {
                    if (strtolower($selectedValue) == strtolower($value)) {
                        $item['checked'] = true;
                        if ($facetName == "category" || $facetName == "type" || $facetName == "subtype")
                            $result['selectedOption'] = $item;
                    }
                }

                $result['items'][] = $item;
            }
        }
        return $result;
    }

    /**
     * TODO: This logic should move to FE
     * @param $currentPage
     * @param $pageUrl
     * @param $totalResults
     * @return mixed
     */
    public function generatePager($currentPage, $pageUrl, $totalResults) {
        $maxResultsPerPage = 16;
        $maxPagers = 5;
        $totalPages = intval(ceil($totalResults/$maxResultsPerPage));

        //defaults
        $result['total']                     = $totalPages;
        $result['current']                   = $currentPage;
        $result['ellipses']['next']          = false;
        $result['ellipses']['previous']      = false;

        if ($totalPages > $maxPagers) {
            if ($currentPage < $totalPages - ceil($maxPagers/2)) {
                $result['items']['last']['href'] = sprintf($pageUrl, $totalPages);
                $result['items']['last']['rel'] = 'last';

                $midpointlast = ceil(($currentPage + $totalPages)/2);
                $result['items']['midpointlast']['href'] = sprintf($pageUrl, $midpointlast);
                $result['items']['midpointlast']['rel'] = 'midpointlast';
            }
            if ($currentPage > ceil($maxPagers/2)) {
                $result['items']['first']['href'] = sprintf($pageUrl, 1);
                $result['items']['first']['rel'] = 'first';

                if($currentPage > 9) {
                    $midpointfirst = ceil((1 + $currentPage)/2);
                    $result['items']['midpointfirst']['href'] = sprintf($pageUrl, $midpointfirst);
                    $result['items']['midpointfirst']['rel'] = 'midpointfirst';
                }

            }
        }

        // previous url
        if ($currentPage > 1 && $currentPage <= $totalPages) {
            $result['ellipses']['previous'] = true;
            $result['items']['previous']['href'] = sprintf($pageUrl, ($currentPage - 1));
            $result['items']['previous']['rel'] = '';
            if (($currentPage - 1) == 1)
                $result['items']['previous']['rel'] = 'first';
        }

        // next url
        if ($currentPage >= 1 && $currentPage < $totalPages) {
            $result['ellipses']['next'] = true;
            $result['items']['next']['href'] = sprintf($pageUrl, ($currentPage + 1));
            $result['items']['next']['rel'] = '';
            if ($currentPage+1 == $totalPages)
                $result['items']['next']['rel'] = 'last';
        }

        // Create pages
        $pages = [];
        $pageCounter = 0;
        $pageNumber  = intval($currentPage - floor($maxPagers / 2));
        if ($pageNumber < 1)
            $pageNumber = 1;
        if ($pageNumber + $maxPagers >= $totalPages) {
            $pageNumber = $totalPages - $maxPagers + 1;
        }

        while($pageCounter < $maxPagers) {
            if ($pageNumber >= 1 && $pageNumber <= $totalPages) {
                $pages[$pageNumber]["href"] = sprintf($pageUrl, $pageNumber);
                if ($pageNumber == 1)
                    $pages[$pageNumber]["rel"] = 'first';
                if ($pageNumber == $totalPages)
                    $pages[$pageNumber]["rel"] = 'last';
            }
            $pageCounter++;
            $pageNumber++;
        }

        $result['items']['pages'] = $pages;

        return $result;
    }


    /**
     * Add a request param.
     *
     * If you add a request param that already exists the param will be converted into a multivalue param,
     * unless you set the overwrite param to true.
     *
     * Empty params are not added to the request. If you want to empty a param disable it you should use
     * remove param instead.
     *
     * @param string       $key
     * @param string|array $value
     * @param bool         $overwrite
     */
    public function addParam($key, $value, $overwrite = true){
        if (null !== $value) {
            if (!$overwrite && isset($this->params[$key])) {
                if (!is_array($this->params[$key])) {
                    $this->params[$key] = [$this->params[$key]];
                }
                $this->params[$key][] = $value;
            } else {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Usefull when you do a new search
     */
    public function resetParams() {
        $this->params = [];
    }

    /**
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * @param String $paramName
     * @return bool|mixed
     */
    public function getParam(String $paramName) {
        if (isset($this->params[$paramName]))
            return $this->params[$paramName];
        return false;
    }

    /**
     * @param String $paramName
     * @return bool
     */
    public function requestHas(String $paramName) {
        return ($this->request->query->has($paramName) && (!empty($this->request->query->get($paramName)) || $this->request->query->get($paramName) === 0));
    }

    /**
     * @param String $paramName
     * @return mixed
     */
    public function requestGet(String $paramName) {
        $paramValue = $this->request->query->get($paramName);
        if (is_string($paramValue))
            $paramValue = trim($paramValue);
        if (!empty($paramValue)) {
            $this->addParam($paramName, $paramValue);
        }
        return $paramValue;
    }

    /**
     * @param $client
     */
    public function setClient($client) {
        $this->client = $client;
    }

    /**
     * @return Query
     */
    public function getQuery() {
        return $this->query;
    }
}