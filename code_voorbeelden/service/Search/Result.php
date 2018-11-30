<?php

namespace TradusBundle\Service\Search;

use http\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TradusBundle\Entity\Seller;

/**
 * Class Result
 *
 * @package TradusBundle\Service\Search
 */
class Result {

    /**
     * Response object.
     * @var Response
     */
    protected $response;

    /**
     * Decoded response data.
     * @var array
     */
    public $data;

    /**
     * Query used for this request.
     * @var QueryInterface
     */
    protected $query;

    /**
     * Solr numFound.
     * @var int
     */
    protected $numberFound = 0;

    /**
     * Document instances array.
     * @var array
     */
    protected $documents = [];

    /**
     * Facet_fields array.
     * @var array
     */
    protected $facet_fields = [];

    /**
     * Stats fields array.
     * @var array
     */
    protected $stats_fields = [];

    /**
     * Suggestions array
     * @var array
     */
    protected $suggestions = [];

    /**
     * @var array
     */
    protected $moreLikeThis = [];

    /**
     * Is the result set parsed?
     * @var bool
     */
    protected $parsed = false;

    /**
     * Result constructor.
     * @param QueryInterface $query
     * @param Response $response
     */
    public function __construct(QueryInterface $query, Response $response) {
        $this->query = $query;
        $this->response = $response;

        // check status for error (range of 400 and 500)
        $statusNum = floor($response->getStatusCode() / 100);
        if (4 == $statusNum || 5 == $statusNum) {
            throw new HttpException($response->getStatusCode(), $response->getStatusMessage().'\n'.$response->getBody());
        }
        $this->parseResponse();
    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return QueryInterface
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getNumberFound() {
        $this->parseResponse();
        return $this->numberFound;
    }

    /**
     * @return array
     */
    public function getDocuments() {
        $this->parseResponse();
        return $this->documents;
    }

    public function setDocuments(array $documents) {
        $this->documents = $documents;
    }

    /**
     * @return array
     */
    public function getFacetFields() {
        $this->parseResponse();
        return $this->facet_fields;
    }

    /**
     * @return array
     */
    public function getStatsFields() {
        $this->parseResponse();
        return $this->stats_fields;
    }

    /**
     * @param string $field
     * @return bool|mixed
     */
    public function getStatsField(string $field) {
        $this->parseResponse();
        if (!isset($this->stats_fields[$field])) {
            return false;
        }
        return $this->stats_fields[$field];
    }


    /**
     * @param string $field
     * @return bool|mixed
     */
    public function getFacetField(string $field) {
        $this->parseResponse();
        if (!isset($this->facet_fields[$field])) {
            return false;
        }
        return $this->facet_fields[$field];
    }

    /**
     * @return array
     */
    public function getSuggestions() {
        $this->parseResponse();
        return $this->suggestions;
    }

    /**
     * Shuffels the documents randomly
     * @return $this
     */
    public function shuffleDocuments() {
        $this->parseResponse();
        if (empty($this->documents) === false) {
            shuffle($this->documents);
            $this->data['response']['docs'] = $this->documents;
        }
        return $this;
    }

    /**
     * Will slice the list of documents to the given limit
     * @param int $limit
     * @return $this
     */
    public function  limitDocuments(int $limit) {
        $this->parseResponse();
        if (empty($this->documents) === false) {
            // Give back less items
            if (count($this->documents) > $limit) {
                $this->documents = array_slice($this->documents, 0, $limit);
            }
            $this->data['response']['docs'] = $this->documents;
        }
        return $this;
    }

    /**
     * @param string $columnName
     * @param $value
     * @return $this
     */
    public function filterDocuments(string $columnName, $value) {
        $this->parseResponse();
        if (empty($this->documents) === false) {
            $this->documents = array_filter($this->documents, function ($document) use ($columnName, $value) {
                if (!isset($document[$columnName]))
                    return true;
                if (is_array($document[$columnName])) {
                    return !in_array($value, $document[$columnName]);
                } else
                    return ($document[$columnName] != $value);
            });
            $this->data['response']['docs'] = $this->documents;
        }
        return $this;
    }

    /**
     * Boosting gold and silver sellers to the top, and option to filterout free sellers
     *
     * @param bool $filterFreeSellerType
     * @return $this
     */
    public function boostSellerTypesDocuments(bool $filterFreeSellerType = true, $gold = 2, $silver = 1 ) {
        $this->parseResponse();
        if (empty($this->documents) === false) {
            $newDocuments = [];
            $goldCount    = 0;
            $silverCount  = 0;

            foreach ($this->documents as $position => $document) {
                // Filter Free sellers out of the results (to display)
                if ($filterFreeSellerType !== false && (@$document[SearchService::SEARCH_FIELDS_SELLER_TYPE] == Seller::SELLER_TYPE_FREE )) {
                    unset($this->documents[$position]);
                    continue;
                }
                // Boost gold offers higher in the results
                if (@$document[SearchService::SEARCH_FIELDS_SELLER_TYPE] == Seller::SELLER_TYPE_PACKAGE_GOLD && $goldCount < $gold) {
                    $newDocuments[] = $document;
                    unset($this->documents[$position]);
                    $goldCount++;
                }
                // Boost silver offers higher in the results
                if (@$document[SearchService::SEARCH_FIELDS_SELLER_TYPE] == Seller::SELLER_TYPE_PACKAGE_SILVER && $silverCount < $silver) {
                    $newDocuments[] = $document;
                    unset($this->documents[$position]);
                    $silverCount++;
                }
            }

            //Merge the remaining results into the new result set
            $this->documents = array_merge($newDocuments, $this->documents);
            $this->result->data['response']['docs'] = $this->documents;
        }
        return $this;
    }

    /**
     * Replaces the return documents with more like this items from document id.
     * @param int $id
     * @return $this
     */
    public function replaceDocumentsWithMoreLikeThis(int $id) {
        $this->parseResponse();
        if (isset($this->moreLikeThis[$id]['docs'])) {
            $this->documents = $this->moreLikeThis[$id]['docs'];
            $this->data['response']['docs'] = $this->documents;
        }
        return $this;
    }

    /**
     * @param string $text
     * @param string $columnName
     * @return $this
     */
    public function orderBySimilarity(string $text, string $columnName = "title_en") {
        $this->parseResponse();
        if (empty($this->documents) === false) {
            $newDocuments = [];
            $tokens = $this->tokenizeString($text);
            foreach ($this->getDocuments() as $doc) {
                $tokens2 = $this->tokenizeString($doc[$columnName]);
                $score = $this->cosinusTokens($tokens, $tokens2);
                $doc['similarityScore'] = $score;
                $newDocuments[] = $doc;
            }
            uasort($newDocuments, function ($a, $b) {
                return $b['similarityScore'] <=> $a['similarityScore'];
            });

            $this->documents = $newDocuments;
            $this->data['response']['docs'] = $this->documents;
        }

        return $this;
    }

    /**
     * @param string $text
     * @return array
     */
    private function tokenizeString($text) {
        $tokens = [];
        if (strlen($text) > 1) {
            $text = mb_strtolower($text, 'UTF-8');
            // We don't want to match the keyword other
            $text = str_replace("other", '', $text);
            // Remove the year, we want to match on make and model
            $text = preg_replace("( - [0-9]{4})", '', $text);
            $text = str_replace("-", '', $text);
            $text = str_replace(".", '', $text);
            $text = str_replace("/", ' ', $text);
            $text = str_replace("+", ' ', $text);
            $text = str_replace("  ", ' ', $text);
            $text = trim($text);
            if (preg_match_all('~\p{Latin}{2,}|\p{Han}+|[0-9]{2,}|(\p{Latin}{1}|[0-9]{1}){2,}~u', $text, $matches))
                $tokens = $matches[0];
        }
        return $tokens;
    }

    /**
     * Cosine similarity of sets with tokens
     * sim(a, b) = (a・b) / (||a|| * ||b||)
     *
     * @param array $a
     * @param array $b
     * @return mixed
     */
    private function cosinusTokens(array $tokensA, array $tokensB) {
        $dotProduct = $normA = $normB = 0;
        $uniqueTokensA = $uniqueTokensB = array();
        $uniqueMergedTokens = array_unique(array_merge($tokensA, $tokensB));

        foreach ($tokensA as $token) $uniqueTokensA[$token] = 0;
        foreach ($tokensB as $token) $uniqueTokensB[$token] = 0;

        foreach ($uniqueMergedTokens as $token) {
            $x = isset($uniqueTokensA[$token]) ? 1 : 0;
            $y = isset($uniqueTokensB[$token]) ? 1 : 0;
            $dotProduct += $x * $y;
            $normA += $x;
            $normB += $y;
        }

        return ($normA * $normB) != 0
            ? $dotProduct / sqrt($normA * $normB)
            : 0;
    }

    /**
     *
     */
    public function parseResponse() {
        if ($this->parsed == true)
            return;

        $data = $this->getData();

        if ($this->query->getType() == QueryInterface::TYPE_SELECT) {
            $this->numberFound = $data['response']['numFound'];
            if (isset($data['response']['docs']) && count($data['response']['docs'])) {
                foreach ($data['response']['docs'] as $doc) {
                    $this->documents[] = $doc;
                }
            }

            if (empty($data['facet_counts']) === false) {
                if (empty($data['facet_counts']['facet_fields']) === false) {
                    foreach ($data['facet_counts']['facet_fields'] as $facetFieldName => $facetValue)
                        $this->facet_fields[$facetFieldName] = $this->convertToKeyValueArray($facetValue);
                }
            }

            if (empty($data['stats']) === false) {
                if (empty($data['stats']['stats_fields']) === false) {
                    foreach ($data['stats']['stats_fields'] as $statsFieldName => $statsValue)
                        $this->stats_fields[$statsFieldName] = $statsValue;
                }
            }

            if (empty($data['moreLikeThis']) === false) {
                $this->moreLikeThis = $data['moreLikeThis'];
            }
        }

        if ($this->query->getType() == QueryInterface::TYPE_SUGGEST) {
            if (empty($data['suggest'][$this->query->getDictionary()][$this->query->getQuery()]) === false) {
                $this->suggestions = $data['suggest'][$this->query->getDictionary()][$this->query->getQuery()]['suggestions'];
                $this->numberFound = $data['suggest'][$this->query->getDictionary()][$this->query->getQuery()]['numFound'];
            }
        }

        $this->parsed = true;
    }

    /**
     * Get Solr response data.
     * Includes a lazy loading mechanism: JSON body data is decoded on first use and then saved for reuse.
     * @throws RuntimeException
     *
     * @return array
     */
    public function getData() {
        if ($this->data === null) {
            $this->data = json_decode($this->response->getBody(), true);
        }
        if ($this->data === null) {
            throw new \UnexpectedValueException('Solr JSON response could not be decoded');
        }
        return $this->data;
    }

    /**
     * Converts a flat key-value array (alternating rows) as used in Solr JSON results to a real key value array.
     *
     * @param array $data
     * @return array
     */
    public function convertToKeyValueArray($data) {
        $result = [];
        while (count($data)) {
            list($key, $value) = array_splice($data, 0, 2);
            $result[$key] = $value;
        }
        return $result;
    }

}
