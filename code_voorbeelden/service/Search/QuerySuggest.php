<?php

namespace TradusBundle\Service\Search;

/**
 * Class QuerySuggest
 *
 * @package TradusBundle\Service\Search
 */
class QuerySuggest extends BaseQuery implements QueryInterface {
    const DEFAULT_BUILD = false;
    const DEFAULT_DICTIONARY = 'mySuggester';
    const DEFAULT_QUERY = '';

    /**
     * Query Type, like SELECT or UPDATE, DELETE
     * @var string
     */
    protected $type = self::TYPE_SUGGEST;

    /**
     * Default options.
     * @var array
     */
    protected $options = [
        'build'            => self::DEFAULT_BUILD,
        'dictionary'       => self::DEFAULT_DICTIONARY,
        'q'                => self::DEFAULT_QUERY,
    ];

    /**
     * @param bool $value
     * @return $this
     */
    public function setBuild(bool $value) {
        $this->setOption('build', $value);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getBuild() {
        return $this->getOption('build');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDictionary(string $value) {
        $this->setOption('dictionary', $value);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getDictionary() {
        return $this->getOption('dictionary');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setQuery(string $value) {
        $this->setOption('q', $value);
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getQuery() {
        return $this->getOption('q');
    }

    /**
     * Creates request parameters for SOLR
     *
     * @param Request $request
     * @return Request
     */
    public function createRequest(Request $request): Request {
        $request->setType($this->getType());

        $request->addParam('suggest', true);
        $request->addParam('suggest.build', $this->getBuild());
        $request->addParam('suggest.dictionary', $this->getDictionary());
        $request->addParam('suggest.q', $this->getQuery());
        $request->addParam('wt', 'json');

        return $request;
    }
}