<?php

namespace TradusBundle\Service\Search;

/**
 * Class Query
 *
 * @package TradusBundle\Service\Search
 */
class Query extends BaseQuery implements QueryInterface {

    const DEFAULT_ROWS      = 16;
    const DEFAULT_START     = 0;
    const DEFAULT_FIELDS    = '*,score';
    const DEFAULT_QUERY     = '*:*';
    const DEFAULT_FACET     = FALSE;
    const DEFAULT_STATS     = FALSE;
    const DEFAULT_EDISMAX   = FALSE;
    const DEFAULT_MLT       = FALSE;
    const DEFAULT_WS        = 'json';
    const DEFAULT_DEBUG     = FALSE;


    /**
     * Items to sort on.
     * @var array
     */
    protected $sorts = [];

    /**
     * Fields to fetch.
     * @var array
     */
    protected $fields = [];

    /**
     * Fields to fetch for facet data.
     * @var array
     */
    protected $facet_fields = [];

    /**
     * Fields to fetch for stats data.
     * @var array
     */
    protected $stats_fields = [];

    /**
     * Query Type, like SELECT or UPDATE, DELETE
     * @var string
     */
    protected $type = self::TYPE_SELECT;

    /**
     * Default options.
     * @var array
     */
    protected $options = [
        'rows'                  => self::DEFAULT_ROWS,
        'start'                 => self::DEFAULT_START,
        'fields'                => self::DEFAULT_FIELDS,
        'query'                 => self::DEFAULT_QUERY,
        'facet'                 => self::DEFAULT_FACET,
        'stats'                 => self::DEFAULT_STATS,
        'debug'                 => self::DEFAULT_DEBUG,
        'mlt'                   => self::DEFAULT_MLT,
        'mlt.count'             => 6,
        'mlt.fl'                => 'title_en',
        'mlt.mintf'             => 1,
        'mlt.boost'             => true,
        'edismax'               => self::DEFAULT_EDISMAX,
        'edismax.boost'         => '',
        'edismax.boost_query'   => '',
    ];

    /**
     * Fields to fetch for facet data with custom limit.
     * @var array
     */
    protected $facet_limits = [];

    public function __construct() {
        foreach($this->options as $option => $value) {
            switch($option) {
                case ('query'): $this->setQuery($value); break;
                case ('start'): $this->setStart((int) $value); break;
                case ('fields'): $this->addFields($value); break;
                case ('rows'): $this->setRows((int) $value); break;
                case ('facet'):
                    if ($value == true)
                        $this->enableFacet();
                    break;
                case ('stats'):
                    if ($value == true)
                        $this->enableStats();
                    break;
                case ('edismax'):
                    if ($value == true)
                        $this->enableEdismax();
                    break;
                    case ('mlt'):
                    if ($value == true)
                        $this->enableMlt();
                    break;
            }
        }
    }

    /**
     * Enables MoreLikeThis
     * @return $this
     */
    public function enableMlt() {
        $this->setOption('mlt', true);
        return $this;
    }

    /**
     * @return $this
     */
    public function enableEdismax() {
        $this->setOption('edismax', true);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getEdismax() {
        return $this->getOption('edismax');
    }

    /**
     * @return $this
     */
    public function enableDebug() {
        $this->setOption('debug', true);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getDebug() {
        return $this->getOption('debug');
    }

    /**
     * @return $this
     */
    public function enableFacet() {
        $this->setOption('facet', true);
        return $this;
    }

    /**
     * @return $this
     */
    public function disableFacet() {
        $this->setOption('facet', false);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getFacet() {
        return $this->getOption('facet');
    }

    /**
     * @return $this
     */
    public function enableStats() {
        $this->setOption('stats', true);
        return $this;
    }

    /**
     * @return $this
     */
    public function disableStats() {
        $this->setOption('stats', false);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getStats() {
        return $this->getOption('stats');
    }

    /**
     * Set the number of results to return
     * @param int $rows
     * @return Query
     */
    public function setRows(int $rows = self::DEFAULT_ROWS) {
        $this->setOption('rows', $rows);
        return $this;
    }

    /**
     * Get the number of results to return
     * @return bool|mixed
     */
    public function getRows() {
        return $this->getOption('rows');
    }

    /**
     * Set the start offset.
     * @param int $start
     * @return Query
     */
    public function setStart(int $start = self::DEFAULT_START) {
        $this->setOption('start', $start);
        return $this;
    }

    /**
     * Get the start offset.
     * @return int
     */
    public function getStart() {
        return $this->getOption('start');
    }

    /**
     * Add a sort, default order is DESC
     * @param string $sort
     * @param string $order
     * @return Query
     */
    public function addSort(string $sort, string $order = self::SORT_DESC) {
        $this->sorts[$sort] = $order;
        return $this;
    }

    /**
     * Get a list of the sorts.
     * @return array
     */
    public function getSorts() {
        return $this->sorts;
    }

    /**
     * Set the raw query string.
     *
     * Overwrites the current value. You are responsible for the correct
     * escaping of user input.
     *
     * @param string $query
     * @param null|array $bindValues
     * @return Query
     */
    public function setQuery(string $query, array $bindValues = null) {
        if (null !== $bindValues) {
            $query = $this->assembleQueryWithValues($query, $bindValues);
        }

        if ($query == '') {
            $query = self::DEFAULT_QUERY;
        }

        $query = trim($query);
        $this->setOption('query', $query);
        return $this;
    }

    /**
     * Get the query string.
     * @return string
     */
    public function getQuery() {
        return $this->getOption('query');
    }

    public function addQuery(string $field, $value, string $operator = self::OPERATOR_AND){
        if (!empty($value) && !empty($field)) {
            if (is_string($value))
                $value = $this->escapePhrase($value);
            $this->addRawQuery($field, $value, $operator);
        }
        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function replaceRawQueryField(string $field, string $value) {
        if (!empty($field) && is_string($field) && !empty($value) && is_string($value)) {
            $currentQuery = $this->getQuery();
            $pattern = '/'.$field.':([\p{L}0-9(" )\[\]{}*-]*["\]}\)]{1}|$)/u';
            $replacement = $field.':'.$value;
            $query = preg_replace($pattern, $replacement, $currentQuery);
            $this->setQuery($query);
        }
        return $this;
    }

    /**
     * Add a field to the query with operator (AND OR)
     *
     * @param string $field
     * @param array|string $value
     * @param string $operator
     * @return $this
     */
    public function addRawQuery(string $field, $value, string $operator = self::OPERATOR_AND, string $operatorArray = self::OPERATOR_OR) {
        $field = trim($field) . ':%s';
        $query = $this->getQuery();

        if (is_string($value)) {
            $value = trim($value);
        }
        if (is_array($value)) {
            $value = $this->escapePhrase($value);
            $value = '('.implode(' '.$operatorArray.' ', $value).')';
        }

        if ($query == self::DEFAULT_QUERY) {
            $this->setQuery($field, [$value]);
        } else {
            $this->setQuery($query . ' ' . $operator . ' ' . $field, [$value]);
        }

        return $this;
    }

    /**
     * @param string $query
     * @param array $bindValues
     * @return string
     */
    public function assembleQueryWithValues(string $query, array $bindValues) {
        return vsprintf($query, $bindValues);
    }

    /**
     * Render a range query.
     *
     * From and to can be any type of data. For instance int, string or point.
     * If they are null, then '*' will be used.
     *
     * Example: rangeQuery('store', '45,-94', '46,-93')
     * Returns: store:[45,-94 TO 46,-93]
     *
     * Example: rangeQuery('store', '5', '*', false)
     * Returns: store:{5 TO *}
     *
     * @param string $field
     * @param string $from
     * @param string $to
     * @param bool   $inclusive
     *
     * @return string
     */
    public function rangeQuery($field, $from = null, $to = null, $inclusive = true) {
        return $field.':'.$this->createRangeValue($from, $to, $inclusive);
    }

    private function createRangeValue($from = null, $to = null, $inclusive = true) {

        if (null === $from) {
            $from = '*';
        } else {
            $from = $this->escapePhrase($from);
        }

        if (null === $to) {
            $to = '*';
        } else {
            $to = $this->escapePhrase($to);
        }

        if ($inclusive)
            return '['.$from.' TO '.$to.']';

        return '{'.$from.' TO '.$to.'}';
    }

    /**
     * @param string $field
     * @param null $from
     * @param null $to
     * @param string $operator
     * @param bool $inclusive
     * @return $this
     */
    public function addRangeQuery(string $field, $from = null, $to = null, $operator = self::OPERATOR_AND, $inclusive = true) {
        $value = $this->createRangeValue($from, $to, $inclusive);
        $this->addRawQuery($field, $value, $operator);
        return $this;
    }

    /**
     * Escapes the input
     *
     * @param $input
     * @return string
     */
    static public function escapePhrase($input) {
        if (is_array($input)) {
            $value = [];
            foreach($input as $item) {
                array_push($value, '"'.preg_replace('/("|\\\)/', '\\\$1', $item).'"');
            }
            return $value;
        } else
            return '"'.preg_replace('/("|\\\)/', '\\\$1', $input).'"';
    }

    /**
     * Returns the StatsFields that has been set
     * @return array
     */
    public function getStatsFields(): array {
        return $this->stats_fields;
    }

    /**
     * @param $stats_fields
     * @return Query
     */
    public function addStatsFields($stats_fields) {
        if (is_string($stats_fields)) {
            $stats_fields = explode(',', $stats_fields);
            $stats_fields = array_map('trim', $stats_fields);
        }
        foreach ($stats_fields as $stats_field) {
            $this->addStatsField($stats_field);
        }
        return $this;
    }

    /**
     * Returns the FacetFields that has been set
     * @return array
     */
    public function getFacetFields(): array {
        return $this->facet_fields;
    }

    /**
     * Specify a stats field to return in the resultset.
     * @param string $stats_field
     * @return Query
     */
    public function addStatsField(string $stats_field) {
        $this->stats_fields[$stats_field] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearStatsFields() {
        $this->stats_fields = [];
        return $this;
    }

    /**
     * @param array $stats_fields
     * @return Query
     */
    public function setStatsFields(array $stats_fields) {
        $this->clearStatsFields();
        $this->addStatsFields($stats_fields);
        return $this;
    }


    /**
     * @param array $facet_fields
     * @return Query
     */
    public function setFacetFields(array $facet_fields) {
        $this->clearFacetFields();
        $this->addFacetFields($facet_fields);
        return $this;
    }

    /**
     * @param $facet_fields
     * @return Query
     */
    public function addFacetFields($facet_fields) {
        if (is_string($facet_fields)) {
            $facet_fields = explode(',', $facet_fields);
            $facet_fields = array_map('trim', $facet_fields);
        }
        foreach ($facet_fields as $facet_field) {
            $this->addFacetField($facet_field);
        }
        return $this;
    }

    /**
     * Specify a field to return in the resultset.
     * @param string $facet_field
     * @return Query
     */
    public function addFacetField(string $facet_field) {
        $this->facet_fields[$facet_field] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearFacetFields() {
        $this->facet_fields = [];
        return $this;
    }

    /**
     * @param string $boost_value
     * @return $this
     */
    public function setEdismaxBoost(string $boost_value) {
        $this->setOption('edismax.boost', $boost_value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEdismaxBoost() {
        return $this->getOption('edismax.boost');
    }

    /**
     * @return mixed
     */
    public function getEdismaxBoostQuery() {
        return $this->getOption('edismax.boost_query');
    }

    /**
     * @param string $field
     * @param $value
     * @param float $boost
     * @return $this
     */
    public function addEdismaxBoostQuery(string $field, $value, float $boost) {
        if (!empty($value) && !empty($field)) {
            if (is_string($value))
                $value = $this->escapePhrase($value);
            $this->addRawEdismaxBoostQuery($field, $value, $boost);
        }
        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param float $boost
     * @param string $operatorArray
     * @return $this
     */
    public function addRawEdismaxBoostQuery(string $field, $value,  float $boost, string $operatorArray = self::OPERATOR_OR) {
        $field = trim($field) . ':%s';
        $query = $this->getEdismaxBoostQuery();

        if (is_string($value)) {
            $value = trim($value);
        }
        if (is_array($value)) {
            $value = $this->escapePhrase($value);
            $value = '('.implode(' '.$operatorArray.' ', $value).')';
        }

        if ($query == '') {
            $this->setEdismaxBoostQuery($field, [$value], $boost);
        } else {
            $this->setEdismaxBoostQuery($query . ' ' . $field, [$value], $boost);
        }

        return $this;
    }

    /**
     * Set the raw edismax boost query string.
     *
     * Overwrites the current value. You are responsible for the correct
     * escaping of user input.
     *
     * @param string $query
     * @param null|array $bindValues
     * @param float $boost
     * @return Query
     */
    public function setEdismaxBoostQuery(string $query, array $bindValues = null, float $boost) {
        if (null !== $bindValues) {
            $query = $this->assembleQueryWithValues($query, $bindValues);
        }

        if ($query == '') {
            return $this;
        }

        $query = trim($query);
        $query = $query.'^'.$boost;
        $this->setOption('edismax.boost_query', $query);
        return $this;
    }

    /**
     * Set multiple fields.
     * This overwrites any existing fields
     *
     * @param array $fields
     * @return Query
     */
    public function setFields(array $fields) {
        $this->clearFields();
        $this->addFields($fields);
        return $this;
    }

    /**
     * Specify a field to return in the resultset.
     * @param string $field
     * @return $this
     */
    public function addField(string $field) {
        $this->fields[$field] = true;
        return $this;
    }

    /**
     * Specify multiple fields to return in the resultset.
     *
     * @param string|array $fields can be an array or string with comma separated fieldnames
     * @return Query
     */
    public function addFields($fields) {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
        }
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }

    /**
     * Remove all fields from the field list.
     * @return Query
     */
    public function clearFields() {
        $this->fields = [];
        return $this;
    }

    /**
     * Get the list of fields.
     * @return array
     */
    public function getFields() {
        return array_keys($this->fields);
    }

    public function getMlt() {
        return $this->getOption('mlt');
    }

    /**
     * Creates request parameters for SOLR
     *
     * @param Request $request
     * @return Request
     */
    public function createRequest(Request $request): Request {
        $request->setType($this->getType());

        $request->addParam("q", $this->getQuery());
        $request->addParam('start', $this->getStart());
        $request->addParam('rows', $this->getRows());
        $request->addParam('fl', implode(',', $this->getFields()));
        $request->addParam('ws', self::DEFAULT_WS);

        if ($this->getFacet() === true) {
            $request->addParam('facet', 'on');
            $facetFields = $this->getFacetFields();
            if (count($facetFields)) {
                foreach ($facetFields as $facetField => $value) {
                    $request->addParam('facet.field', $facetField);
                }
            }
        }

        if (count($this->facet_limits)) {
            foreach($this->facet_limits as $facetName => $facetLimit) {
                $request->addParam('f.'.$facetName.'.facet.limit', $facetLimit);
            }
        }

        if ($this->getStats() === true) {
            $request->addParam('stats', 'true');
            $statsFields = $this->getStatsFields();
            if (count($statsFields)) {
                foreach ($statsFields as $statsField => $value) {
                    $request->addParam('stats.field', $statsField);
                }
            }
        }

        if ($this->getEdismax() === true) {
            $request->addParam('defType', 'edismax');
            $request->addParam('boost', $this->getEdismaxBoost());
            $request->addParam('bq', $this->getEdismaxBoostQuery());
        }

        if ($this->getMlt() === true) {
            $request->addParam('mlt', true);
            $request->addParam('mlt.count', $this->getOption('mlt.count'));
            $request->addParam('mlt.fl', $this->getOption('mlt.fl'));
            $request->addParam('mlt.mintf', $this->getOption('mlt.mintf'));
            $request->addParam('mlt.boost', $this->getOption('mlt.boost'));
        }

        if ($this->getDebug() === true) {
            $request->addParam('debugQuery', 'on');
        }


        /*
         * We can overide some defaults:
         */
        // pecifies the default operator for query expressions, overriding the default operator specified in the Schema. Possible values are "AND" or "OR".
        // $request->addParam('q.op', 'AND');

        // Specifies a default field, overriding the definition of a default field in the Schema.
        // $request->addParam('df', );


        $sort = [];
        foreach ($this->getSorts() as $field => $order) {
            $sort[] = $field.' '.$order;
        }
        if (0 !== count($sort)) {
            $request->addParam('sort', implode(',', $sort));
        }

        return $request;
    }

    /**
     * Specify a facet field limit for a particular facet field.
     * @param string $facetName, int $limit
     * @return Query
     */

    public function setFacetLimit(string $facetName, int $limit) {
        $this->facet_limits[$facetName] = $limit;
    }

}