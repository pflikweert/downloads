<?php

namespace AppBundle\Controller;

use TradusBundle\Entity\OfferInterface;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Transformer\OfferSearchTransformer;

/**
 * @FOSRest\NamePrefix("api_")
 */
class APISearchOfferController extends TradusAPIController {

    /**
     * Search offers
     *
     * @ApiDoc(
     *  section="Search",
     *  resource=false,
     *  description="Search offers",
     *  output="TradusBundle\Entity\Offer"
     * )
     *
     * @QueryParam(name="limit", requirements="\d+", strict=true, nullable=true, description="limit")
     * @QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="page")
     * @QueryParam(name="q", nullable=true, description="query string")
     * @QueryParam(name="year_from", nullable=true, description="from year")
     * @QueryParam(name="year_to", nullable=true, description="to year")
     * @QueryParam(name="cat_l1", nullable=true, description="cat_l1")
     * @QueryParam(name="cat_l2", nullable=true, description="cat_l2")
     * @QueryParam(name="cat_l3", nullable=true, description="cat_l3")
     * @QueryParam(name="make", nullable=true, description="make")
     * @QueryParam(name="country", nullable=true, description="country location")
     * @QueryParam(name="price_from", nullable=true, description="min price")
     * @QueryParam(name="price_to", nullable=true, description="max price")
     * @QueryParam(name="sort", nullable=true, description="order by")
     * @QueryParam(name="ip", nullable=true, description="user ip")
     * @QueryParam(name="locale", nullable=true, description="locale")
     * @QueryParam(name="user_agent", nullable=true, description="locale")
     * @QueryParam(name="seller_id", nullable=true, description="seller_id")

     * @QueryParam(name="seller_slug", nullable=true, description="seller_id")
     *
     * @Get("/search", options={"utf8": true})
     */
    public function getSearchOffers2Action(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $locale = $request->query->get('locale') ?: 'en';
        /* @var SearchService $search */
        $search = $this->get('tradus.search');
        $searchResult = $search->findByRequest($request);
        $result = $search->getTradusResult($searchResult);

        $result['offers']      = (new OfferSearchTransformer($result, $locale, 1, $em))->transform();
        $result['query']       = $request->query->get('q');
        $result['searchQuery'] = $search->getSearchQueryText($locale);

        $alternates = [];
        foreach (OfferInterface::SUPPORTED_LOCALES as $supportedLocale) {
            $alternates[$supportedLocale] = $search->getSearchUrl($supportedLocale);
        }
        $result['alternates'] = $alternates;

        $result['sorts'] = $search->getResultSorts();
        $result['filterOptions'] = ['filterCount' => count($search->getParams())-1, 'resetFilters' => $search->getSearchBaseUrl($locale)];

        // PAGINATION
        $page = $request->query->get('page') ?: 1;
        $searchUrlParameters = $search->getSearchUrlParametersString([SearchService::REQUEST_FIELD_PAGE, SearchService::REQUEST_FIELD_QUERY]);
        if ($searchUrlParameters)
            $searchUrlParameters = '&'. $searchUrlParameters;

        if ($request->query->get('route') == 'favorites') {
            $searchUrl = $request->query->get('page_url') . '?' . 'page=%s' . $searchUrlParameters;
        } else {
            $searchUrl = $search->getSearchUrl($locale) . '?' . 'page=%s' . $searchUrlParameters;
        }
        $result['pager'] = $search->generatePager($page, $searchUrl, $searchResult->getNumberFound());

        //FACETS
        $result['facet'] = $search->getFacetDataResults();

        //REMOVE DATA THAT IS CURRENTLY NOT NEEDED:
        unset($result['response']);
        unset($result['facet_counts']);

        $view = $this->view($result, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @QueryParam(name="query", nullable=false, description="find suggestions by typed in query")
     * @QueryParam(name="build", nullable=true, description="rebuild the dictionary with true")
     *
     * @Get("/autocomplete", options={"utf8": true})
     */
    public function getAutoCompleteSugestionsAction(Request $request) {
        $build = false;
        $searchResult = null;
        $query = null;

        if ($request->query->has('query'))
            $query = $request->query->get('query');

        if ($request->query->has('build') && !empty($request->query->get('build')))
            $build = true;

        if (null !== $query && !empty($query)) {
            /* @var SearchService $search */
            $search = $this->get('tradus.search');
            $searchResult = $search->getAutoCompleteSuggestions($query, $build);
        }

        $view = $this->view($searchResult, Response::HTTP_OK);

        return $this->handleView($view);
    }
}
