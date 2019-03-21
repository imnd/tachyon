<?php
namespace tachyon\db\dbal;

use tachyon\Config,
    tachyon\components\Message;

/**
 * Реализует паттерн "фабричный метод"
 * Инстанциирует соответствующий класс DBAL
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class DbFactory
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var tachyon\components\Message $msg
     */
    protected $msg;
    /**
     * @var tachyon\Config $config
     */
    protected $config;

    /**
     * @return void
     */
    public function __construct(Config $config, Message $msg)
    {
        $this->config = $config;
        $this->msg = $msg;
    }

    /**
     * @return Db
     */
    public function getDb(): Db
    {
        if (is_null($this->db)) {
            if (!$config = $this->config->get('db')) {
                throw new \tachyon\exceptions\DBALException('Не задан параметр конфигурации "db"');
            }
            if (!isset($config['engine'])) {
                throw new \tachyon\exceptions\DBALException('Не задан параметр конфигурации "engine"');
            }
            $config['mode'] = $this->config->get('mode');
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$config['engine']];
            $className = "\\tachyon\\db\\dbal\\$className";

            $this->db = new $className($this->msg, $config);
        }
        return $this->db;
    }
}
