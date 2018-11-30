<?php

namespace TradusBundle\Service\Search;

/**
 * Interface QueryInterface
 *
 * @package TradusBundle\Service\Search
 */
interface QueryInterface
{
    const OPERATOR_AND  = 'AND';
    const OPERATOR_OR   = 'OR';
    const OPERATOR_SPACE   = ' ';

    /**
     * Solr sort mode descending.
     */
    const SORT_DESC     = 'desc';

    /**
     * Solr sort mode ascending.
     */
    const SORT_ASC      = 'asc';

    /**
     * Solr type query's
     * Used to build the url in the Request object
     */
    const TYPE_SELECT  = 'select';
    const TYPE_SUGGEST = 'suggest';

    /**
     * @param Request $request
     * @return Request
     */
    public function createRequest(Request $request): Request;

    /**
     * @return mixed
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type);
}