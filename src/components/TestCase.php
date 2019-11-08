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
     * @var Container
     */
    protected $container;
    /**
     * @var \tachyon\Config
     */
    protected $config;

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->container = new Container;
        $this->config = $this->container->get('\tachyon\Config', [
            'fileName' => 'main-test'
        ]);

        parent::__construct($name, $data, $dataName);
    }
}
