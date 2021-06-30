<?php

namespace tachyon\commands;

use app\ServiceContainer;
use ReflectionException;
use tachyon\Config;
use tachyon\db\dbal\{
    Db, DbFactory
};
use tachyon\exceptions\{
    ContainerException, DBALException
};
use tachyon\db\Migration;

/**
 * Производит миграции
 * usage: tachyon migrate
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class Migrate extends Command
{
    /**
     * @var Db
     */
    protected Db $db;
    protected bool $migrate = false;

    /**
     * @param DbFactory $dbFactory
     *
     * @throws ReflectionException | ContainerException | DBALException
     */
    public function __construct(DbFactory $dbFactory)
    {
        $this->db = $dbFactory->getDb();
    }

    /**
     * @throws ContainerException
     * @throws DBALException
     * @throws ReflectionException
     */
    public function run(): void
    {
        $migrations = $this->db->select('migrations');
        $migrations = $this->db->twitch($migrations, 'name');
        if ($handle = opendir(__DIR__ . Config::APP_DIR . '../../migrations')) {
            while (false !== ($fileName = readdir($handle))) {
                if ($fileName !== '.' && $fileName !== '..') {
                    $ext = substr($fileName, strpos($fileName, '.') + 1);
                    if ($ext !== 'php') {
                        continue;
                    }
                    $migrationName = substr($fileName, 0, -4);
                    if (in_array($migrationName, $migrations)) {
                        continue;
                    }
                    $className = 'migrations\\' . $migrationName;
                    if (class_exists($className)) {
                        $migration = (new ServiceContainer)->get($className);
                        $migration->run();
                        $this->register($migration);
                    }
                }
            }
            closedir($handle);
        }

        echo $this->migrate ? 'All migrations done.' : 'Nothing to migrate';
    }

    /**
     * Создает таблицу migrations если ее нет создает в ней запись о миграции
     *
     * @param Migration $migration
     *
     * @return void
     * @throws DBALException
     */
    public function register(Migration $migration): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `migrations`  (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` tinytext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            `time` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
            PRIMARY KEY (`id`) USING BTREE
        ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;");

        $this->db->insert('migrations', [
            'name' => $migration->getClassName(),
        ]);

        $this->migrate = true;
    }
}
