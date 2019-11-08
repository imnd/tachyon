<?php
namespace tachyon\components;

use
    PHPUnit\Framework\TestCase as BaseTestCase,
    GuzzleHttp\Client as HttpClient,
    tachyon\Config,
    tachyon\dic\Container;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class TestCase extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var \tachyon\Config
     */
    protected $config;
    /**
     * @var GuzzleHttp\Client $client
     */
    protected $httpClient;

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->container = new Container;
        $this->config = $this->container->get(Config::class, [
            'env' => 'test'
        ]);
        $this->httpClient = new HttpClient([
            'base_uri' => $this->config->get('base_url'),
            'timeout' => 2.0,
        ]);

        parent::__construct($name, $data, $dataName);
    }
}
