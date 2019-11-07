<?php
namespace tachyon\components;

use
    PHPUnit\Framework\TestCase as BaseTestCase,
    tachyon\dic\Container;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class TestCase extends BaseTestCase
{
    /**
     * @var Config $config
     */
    protected $config;
    /**
     * @var GuzzleHttp\Client $client
     */
    protected $httpClient;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $config = (new \tachyon\dic\Container)->get('\tachyon\Config', [
            'fileName' => 'main-test'
        ]);
    }
}
