<?php

namespace tachyon\components;

use
    PHPUnit\Framework\TestCase as BaseTestCase,
    GuzzleHttp\Client as HttpClient,
    tachyon\Config;

/**
 * @author imndsu@gmail.com
 */
class TestCase extends BaseTestCase
{
    protected Config $config;

    protected HttpClient $httpClient;

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->config = new Config('test');
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
