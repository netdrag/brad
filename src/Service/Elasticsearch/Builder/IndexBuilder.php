<?php

namespace Invertus\Brad\Service\Elasticsearch\Builder;

use Core_Business_ConfigurationInterface;
use Core_Foundation_Database_EntityManager;
use Invertus\Brad\Config\Setting;

/**
 * Class IndexBuilder
 *
 * @package Invertus\Brad\Service\Elasticsearch\Builder
 */
class IndexBuilder
{
    /**
     * @var Core_Business_ConfigurationInterface
     */
    private $configuration;

    /**
     * @var Core_Foundation_Database_EntityManager
     */
    private $em;

    /**
     * IndexBuilder constructor.
     *
     * @param Core_Business_ConfigurationInterface $configuration
     * @param Core_Foundation_Database_EntityManager $em
     */
    public function __construct(Core_Business_ConfigurationInterface $configuration, Core_Foundation_Database_EntityManager $em)
    {
        $this->configuration = $configuration;
        $this->em = $em;
    }

    /**
     * Build index mappings, setting & etc.
     *
     * @param int $idShop
     *
     * @return array
     */
    public function buildIndex($idShop)
    {
        $numberOfShards = (int) $this->configuration->get(Setting::NUMBER_OF_SHARDS_ADVANCED);
        $numberOfReplicas = (int) $this->configuration->get(Setting::NUMBER_OF_REPLICAS_ADVANCED);
        $refreshInterval = (int) $this->configuration->get(Setting::REFRESH_INTERVAL_ADVANCED);

        $indexSettings = [
            'settings' => [
                'number_of_shards' => $numberOfShards,
                'number_of_replicas' => $numberOfReplicas,
                'refresh_interval' => $refreshInterval.'s',
            ],
            'mappings' => [
                'products' => [
                    'properties' => [
                        'weight' => [
                            'type' => 'double',
                        ],
                    ],
                ],
            ],
        ];

        $indexSettings['mappings']['products']['properties'] =
            array_merge($indexSettings['mappings']['products']['properties'], $this->buildIndexPriceMappings($idShop));

        return $indexSettings;
    }

    /**
     * Build mapping for prices
     *
     * @param int $idShop
     *
     * @return array
     */
    private function buildIndexPriceMappings($idShop)
    {
        $mapping = [];

        $countriesIds = $this->em->getRepository('BradCountry')->findAllIdsByShopId($idShop);
        $currenciesIds = $this->em->getRepository('BradCurrency')->findAllIdsByShopId($idShop);
        $groupsIds = $this->em->getRepository('BradGroup')->findAllIdsByShopId($idShop);

        foreach ($groupsIds as $idGroup) {
            foreach ($countriesIds as $idCountry) {
                foreach ($currenciesIds as $idCurrency) {
                    $mapping['price_group_'.$idGroup.'_country_'.$idCountry.'_currency_'.$idCurrency] = [
                        'type' => 'double',
                    ];
                }
            }
        }

        return $mapping;
    }
}
