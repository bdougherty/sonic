<?php
namespace Sonic;
use Sonic\App;

/**
 * Database
 *
 * @category Sonic
 * @package Database
 * @author Craig Campbell
 */
class Database
{
    const INT = 'int';
    const TINY_INT = 'tinyint';
    const VARCHAR = 'varchar';
    const ENUM = 'enum';
    const DATETIME = 'datetime';
    const TEXT = 'text';
    const MEDIUM_TEXT = 'mediumtext';
    const LONG_TEXT = 'longtext';
    const FLOAT = 'float';

    /**
     * @var string
     */
    const MASTER = 'master';

    /**
     * @var string
     */
    const SLAVE = 'slave';

    /**
     * @var string
     */
    const READ = 'read';

    /**
     * @var string
     */
    const WRITE = 'write';

    /**
     * @var string
     */
    protected $_schema;

    /**
     * @var array
     */
    protected $_connections = array();

    /**
     * constructor
     *
     * @param string $schema
     */
    public function __construct($schema)
    {
        if (!$schema) {
            $schema = $this->getDefaultSchema();
        }
        $this->_schema = $schema;
    }

    /**
     * get default schema
     *
     * @return string
     */
    public function getDefaultSchema()
    {
        $app = App::getInstance();
        $schema = $app->getSetting(App::DEFAULT_SCHEMA) ?: $app->getConfig()->get('db.default_schema');
        return $schema;
    }

    /**
     * prepares a query and returns the statement object
     *
     * @param string $sql
     * @return PDOStatement
     */
    public function prepare($sql)
    {
        $type = $this->_serverForSql($sql);
        $pdo = $this->getPdo($type);
        return $pdo->prepare($sql);
    }

    /**
     * gets a PDO object for a specific connection
     *
     * @param string $type (Database::SLAVE || Database::MASTER)
     * @return PDO
     */
    public function getPdo($type = self::SLAVE)
    {
        if (isset($this->_connections[$type])) {
            return $this->_connections[$type];
        }

        $server = $this->getRandomServer($type);

        $class = self::getDriverClass();

        $pdo = new $class($server['dsn'], $server['user'], $server['password']);
        $pdo->setAttribute($class::ATTR_ERRMODE, $class::ERRMODE_EXCEPTION);
        $pdo->setAttribute($class::ATTR_CASE, $class::CASE_LOWER);
        $pdo->setAttribute($class::ATTR_EMULATE_PREPARES, true);

        $this->_connections[$type] = $pdo;
        return $this->_connections[$type];
    }

    /**
     * returns the class of what driver to use depending on the application setting
     *
     * @return string
     */
    public static function getDriverClass()
    {
        $driver = App::getInstance()->getSetting(App::DB_DRIVER);
        switch ($driver) {
            case App::MYSQL:
                $class = 'Sonic\Database\MySql';
                break;
            case App::MYSQLI:
                $class = 'Sonic\Database\MySqli';
                break;
            default:
                $class = '\PDO';
                break;
        }
        return $class;
    }

    /**
     * gets a random server fot this type based on weights
     *
     * @param string $type (Database::SLAVE || Database::MASTER)
     * @return array
     */
    public function getRandomServer($type)
    {
        $servers = Database\Factory::getServers($this->_schema);

        $matching_servers = $weights = array();
        foreach ($servers as $server) {
            if ($server['type'] == $type) {
                $matching_servers[] = $server;
                $weights[] = $server['weight'] ?: 1;
            }
        }

        // if there are no slave servers specified, use the master
        if (count($matching_servers) == 0 && $type == self::SLAVE) {
            return $this->getRandomServer(self::MASTER);
        }

        if (count($matching_servers) == 0) {
            throw new Database\Exception('no ' . $type . ' servers found matching schema: ' . $this->_schema);
        }

        if (count($matching_servers) == 1) {
            return $matching_servers[0];
        }

        $server_index = Util::getWeightedRandomKey($weights);

        return $matching_servers[$server_index];
    }

    /**
     * figures out what server to use for this SQL
     *
     * @param string $sql
     * @return string
     */
    protected function _serverForSql($sql)
    {
        if ($this->_readOrWrite($sql) == self::WRITE) {
            return self::MASTER;
        }
        return self::SLAVE;
    }

    /**
     * figures out if this is a read or write query
     *
     * @param string $sql
     * @return string
     */
    protected function _readOrWrite($sql)
    {
        if ($this->_isReadQuery($sql)) {
            return self::READ;
        }
        return self::WRITE;
    }

    /**
     * tells us if this is a read query
     *
     * @param string $sql
     * @return bool
     */
    protected function _isReadQuery($sql)
    {
        $bits = explode('*/', $sql);
        $sql = isset($bits[1]) ? $bits[1] : $bits[0];
        $sql = trim($sql);
        return strpos($sql, 'SELECT') === 0 || strpos($sql, 'select') === 0;
    }

    /**
     * gets the database name
     *
     * @return string
     */
    public function getSchema()
    {
        if ($this->_schema === null) {
            $this->_schema = $this->getDefaultSchema();
        }
        return $this->_schema;
    }
}
