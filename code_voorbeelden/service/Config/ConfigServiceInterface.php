<?php

namespace TradusBundle\Service\Config;
use TradusBundle\Entity\Offer;

/**
 * Interface OfferServiceInterface
 *
 * @package TradusBundle\Service\Config
 */
interface ConfigServiceInterface {
    const GROUP_SEARCH      = 'search';
    const GROUP_UNIT_TEST   = 'unittests';
    const GROUP_ALERTS      = 'alerts';
    const GROUP_CRITEO      = 'criteo';
    const GROUP_EMAILS      = 'emails';

    const DEFAULT_SETTINGS = [
        [
            ConfigResult::DATA_NAME             => 'unitTest',
            ConfigResult::DATA_GROUP            => self::GROUP_UNIT_TEST,
            ConfigResult::DATA_DEFAULT_VALUE    => 'Unit test value',
            ConfigResult::DATA_DISPLAY_NAME     => 'Setting for unit testing',
        ]
        // SEARCH SETTINGS
        ,[
            ConfigResult::DATA_NAME             => 'relevancy.boostHasImageScore',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 10.0,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Has Image',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostPriceScore',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 4.0,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Has Good Price',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostSellerTypesScore',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 0.5,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Seller Types',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostTitleScore',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 1.0,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Query in title',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostCountryScore',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 0.1,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Sellers Country',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostCountryList',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => ['NL', 'DE', 'BE', 'AT', 'ES', 'IT', 'FR', 'DA'],
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Sellers Country List',
            ConfigResult::DATA_POSSIBLE_VALUES  => Offer::SUPPORTED_LOCALES,
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostTimeA',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 1.5,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Newer/bumpedup offers higher parameter A',
        ],[
            ConfigResult::DATA_NAME             => 'relevancy.boostTimeB',
            ConfigResult::DATA_GROUP            => self::GROUP_SEARCH,
            ConfigResult::DATA_DEFAULT_VALUE    => 0.05,
            ConfigResult::DATA_DISPLAY_NAME     => 'Relevancy boost: Newer/bumpedup offers higher parameter B',
        ]
        // ALERT RULE MATCHING OFFER
        ,[
            ConfigResult::DATA_NAME             => 'alert.rule.matchingOffer.sendFirstUpdateAfter',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => "12 hour",
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert Matching offer: send first update after time',
        ],[
            ConfigResult::DATA_NAME             => 'alert.rule.matchingOffer.sendUpdateInterval',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => "1 day",
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert Matching offer: update interval time',
        ],[
            ConfigResult::DATA_NAME             => 'alert.rule.matchingOffer.filterLimit',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => 4,
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert Matching offer: amount of offers in the mail',
        ],[
            ConfigResult::DATA_NAME             => 'alert.rule.matchingOffer.filterIncludeCountries',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => ['NL', 'DE', 'BE', 'AT', 'ES', 'IT', 'FR', 'DA'],
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert Matching offer: only offers from these countries',
        ],[
            ConfigResult::DATA_NAME             => 'alert.rule.matchingOffer.filterFreeSellers',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => true,
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert Matching offer: filter free sellers',
        ],[
            ConfigResult::DATA_NAME             => 'alert.limit.updates',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => 3,
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert max updates per timeframe: anount of updates',
        ],[
            ConfigResult::DATA_NAME             => 'alert.limit.timeframe',
            ConfigResult::DATA_GROUP            => self::GROUP_ALERTS,
            ConfigResult::DATA_DEFAULT_VALUE    => "2 hour",
            ConfigResult::DATA_DISPLAY_NAME     => 'Alert max updates per timeframe: time',
        ]
        // CRITEO JOB
        ,[
            ConfigResult::DATA_NAME             => 'criteo.job.filterIncludeCountries',
            ConfigResult::DATA_GROUP            => self::GROUP_CRITEO,
            ConfigResult::DATA_DEFAULT_VALUE    => ['NL', 'DE', 'BE', 'AT', 'ES', 'IT', 'FR', 'DA'],
            ConfigResult::DATA_DISPLAY_NAME     => 'Criteo job, what coutries to include in the export',
        ],[
            ConfigResult::DATA_NAME             => 'criteo.job.filterIncludeSellerTypes',
            ConfigResult::DATA_GROUP            => self::GROUP_CRITEO,
            ConfigResult::DATA_DEFAULT_VALUE    => [1,2,3,4,5],
            ConfigResult::DATA_DISPLAY_NAME     => 'Criteo job, what sellers to include in the export',
        ]
        // EMAILS
        ,[
            ConfigResult::DATA_NAME             => 'emails.bcc.list',
            ConfigResult::DATA_GROUP            => self::GROUP_EMAILS,
            ConfigResult::DATA_DEFAULT_VALUE    => [["jorre.bonjer@olx.com" => "Jorre Bonjer"],["marcos.goiriz@olx.com" => "Marcos Goiriz"], ["miguel@olx.com" => "Miguel Mascarenhas"]],
            ConfigResult::DATA_DISPLAY_NAME     => 'Emails bcc list',
        ]
    ];

}