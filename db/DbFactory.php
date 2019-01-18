<?php
namespace tachyon\db;

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
     * @var \tachyon\db\Db
     */
    private $db;

    /**
     * @return \tachyon\db\Db
     */
    public function getDb()
    {
        if (is_null($this->db)) {
            if (!$config = $this->config->getOption('db')) {
                throw new \tachyon\exceptions\DataBaseException('Не задан параметр конфигурации "db"');
            }
            if (!isset($config['engine'])) {
                throw new \tachyon\exceptions\DataBaseException('Не задан параметр конфигурации "engine"');
            }
            $className = [
                'mysql' => 'MySql',
                'pgsql' => 'PgSql',
            ][$config['engine']];
            $config['mode'] = $this->get('config')->getOption('mode');
            $className = "\\tachyon\\db\\$className";

            $this->db = new $className($config, $this->get('msg'));
        }
        return $this->db;
    }
}
