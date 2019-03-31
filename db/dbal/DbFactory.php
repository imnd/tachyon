<?php
namespace tachyon\db\dbal;

use tachyon\dic\Container,
    tachyon\exceptions\DBALException,
    tachyon\Config,
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
                throw new DBALException('Не задан параметр конфигурации "db"');
            }
            if (!isset($config['engine'])) {
                throw new DBALException('Не задан параметр конфигурации "engine"');
            }
            $config['mode'] = $this->config->get('mode');
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$config['engine']];
            $this->db = (new Container)->get("\\tachyon\\db\\dbal\\$className", $config);
        }
        return $this->db;
    }
}
