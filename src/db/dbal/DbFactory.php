<?php
namespace tachyon\db\dbal;

use ReflectionException;
use tachyon\dic\Container,
    tachyon\exceptions\DBALException,
    tachyon\Config,
    tachyon\components\Message,
    tachyon\exceptions\ContainerException;

/**
 * Реализует паттерн "фабричный метод"
 * Инстанциирует соответствующий класс DBAL
 *
 * @author imndsu@gmail.com
 */
class DbFactory
{
    /**
     * @var Db|null
     */
    private ?Db $db = null;

    /**
     * @var Message $msg
     */
    protected Message $msg;
    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * @param Config       $config
     * @param Message|null $msg
     */
    public function __construct(Config $config, Message $msg = null)
    {
        $this->config = $config;
        $this->msg = $msg ?? new Message;
    }

    /**
     * @return Db
     * @throws DBALException | ReflectionException | ContainerException
     */
    public function getDb(): Db
    {
        if (is_null($this->db)) {
            if (!$options = $this->config->get('db')) {
                throw new DBALException('Не задан параметр конфигурации "db"');
            }
            if (!isset($options['engine'])) {
                throw new DBALException('Не задан параметр конфигурации "engine"');
            }
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$options['engine']];

            $this->db = app()->get( __NAMESPACE__ . "\\$className", compact('options'));
        }
        return $this->db;
    }
}
