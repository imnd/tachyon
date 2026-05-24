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
    private ?Db $db = null;

    public function __construct(protected Config $config, protected Message $msg)
    {
    }

    /**
     * @throws DBALException | ReflectionException | ContainerException
     */
    public function getDb(): Db
    {
        if (!is_null($this->db)) {
            return $this->db;
        }

        if (!$options = $this->config->get('db')) {
            throw new DBALException($this->msg->t('Parameter "db" not set.'));
        }

        if (!isset($options['engine'])) {
            throw new DBALException($this->msg->t('Parameter "engine" not set.'));
        }

        $className = [
            'mysql' => 'MySql',
            'pgsql' => 'PgSql',
        ][$options['engine']];

        $this->db = app()->get( __NAMESPACE__ . "\\$className", compact('options'));

        return $this->db;
    }
}
