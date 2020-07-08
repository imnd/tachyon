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
 * @copyright (c) 2020 IMND
 */
class DbFactory
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var Message $msg
     */
    protected $msg;
    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @return void
     */
    public function __construct(Config $config, Message $msg = null)
    {
        $this->config = $config;
        $this->msg = $msg ?? new Message;
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
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$config['engine']];

            $this->db = (new Container)->get("\\tachyon\\db\\dbal\\$className", compact('config'));
        }
        return $this->db;
    }
}
