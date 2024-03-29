<?php

namespace tachyon\components;

use
    PHPUnit\Framework\TestCase as BaseTestCase,
    GuzzleHttp\Client as HttpClient,
    tachyon\Config,
    tachyon\dic\Container;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class TestCase extends BaseTestCase
{
    /**
     * @var Container
     */
    protected Container $container;
    /**
     * @var Config
     */
    protected Config $config;
    /**
     * @var HttpClient $client
     */
    protected $httpClient;

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->container = app();
        $this->config = $this->container->get(Config::class);
        $baseUrl = $this->config->get('base_url');
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl = "$baseUrl/";
        }
        $this->httpClient = new HttpClient(
            [
                'base_uri' => $baseUrl,
                'timeout' => 10,
                'http_errors' => false,
            ]
        );
        parent::__construct($name, $data, $dataName);
    }
}
