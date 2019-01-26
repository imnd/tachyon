<?php
namespace tachyon\db\dbal;

/**
 * Реализует паттерн "фабричный метод"
 * Инстанциирует соответствующий класс DBAL
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class DbFactory extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Message;

    /**
     * @var Db
     */
    private $db;

    /**
     * @return Db
     */
    public function getDb(): Db
    {
        if (is_null($this->db)) {
            if (!$config = $this->config->getOption('db')) {
                throw new \tachyon\exceptions\DBALException('Не задан параметр конфигурации "db"');
            }
            if (!isset($config['engine'])) {
                throw new \tachyon\exceptions\DBALException('Не задан параметр конфигурации "engine"');
            }
            $config['mode'] = $this->get('config')->getOption('mode');
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$config['engine']];
            $className = "\\tachyon\\db\\dbal\\$className";

            $this->db = new $className($config, $this->get('msg'));
        }
        return $this->db;
    }
}
