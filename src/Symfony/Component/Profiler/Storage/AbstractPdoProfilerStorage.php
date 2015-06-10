<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Storage;

/**
 * Base PDO storage for profiling information in a PDO database.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jan Schumann <js@schumann-it.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
abstract class AbstractPdoProfilerStorage extends AbstractProfilerStorage
{
    protected $dsn;
    protected $username;
    protected $password;
    protected $lifetime;
    protected $db;

    /**
     * Constructor.
     *
     * @param string $dsn      A data source name
     * @param string $username The username for the database
     * @param string $password The password for the database
     * @param int    $lifetime The lifetime to use for the purge
     */
    public function __construct($dsn, $username = '', $password = '', $lifetime = 86400)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->lifetime = (int) $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, $limit, $start = null, $end = null)
    {
        if (null === $start) {
            $start = 0;
        }

        if (null === $end) {
            $end = time();
        }

        list($criteria, $args) = $this->buildCriteria($criteria, $start, $end, $limit);

        $criteria = $criteria ? 'WHERE '.implode(' AND ', $criteria) : '';

        $db = $this->initDb();
        $tokens = $this->fetch($db, 'SELECT * FROM sf_profiler_data '.$criteria.' ORDER BY time DESC LIMIT '.((int) $limit), $args);
        $this->close($db);

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    public function doRead($token)
    {
        $db = $this->initDb();
        $args = array(':token' => $token);
        $data = $this->fetch($db, 'SELECT * FROM sf_profiler_data WHERE token = :token LIMIT 1', $args);
        $this->close($db);
        if (isset($data[0]['data'])) {
            return $data[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($token, array $data, array $indexedData)
    {
        $db = $this->initDb();
        $fields = array_merge(array('created_at'), array_keys($data));
        $args = array(
            ':created_at' => time(),
        );
        foreach ($data as $key => $value) {
            $args[':'.$key] = $value;
        }

        try {
            $this->ensureColumnsExist($data, $indexedData);

            if ($this->has($args[':token'])) {
                $this->exec($db, 'UPDATE sf_profiler_data SET '.implode(', ', array_map(function ($field) { return $field.' = :'.$field; }, $fields)).' WHERE token = :token', $args);
            } else {
                $this->exec($db, 'INSERT INTO sf_profiler_data ('.implode(', ', $fields).') VALUES ('.implode(', ', array_keys($args)).')', $args);
            }
            $this->cleanup();
            $status = true;
        } catch (\Exception $e) {
            $status = false;
        }

        $this->close($db);

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $db = $this->initDb();
        $this->exec($db, 'DELETE FROM sf_profiler_data');
        $this->close($db);
    }

    /**
     * Build SQL criteria to fetch records by ip and url.
     *
     * @param array  $criteria The Criteria
     * @param string $start    The start period to search from
     * @param string $end      The end period to search to
     * @param string $limit    The maximum number of tokens to return
     *
     * @return array An array with (criteria, args)
     */
    abstract protected function buildCriteria(array $criteria, $start, $end, $limit);

    /**
     * Initializes the database.
     *
     * @throws \RuntimeException When the requested database driver is not installed
     */
    abstract protected function initDb();
    abstract protected function ensureColumnsExist(array $data, array $indexedData);

    protected function cleanup()
    {
        $db = $this->initDb();
        $this->exec($db, 'DELETE FROM sf_profiler_data WHERE created_at < :time', array(':time' => time() - $this->lifetime));
        $this->close($db);
    }

    protected function exec($db, $query, array $args = array())
    {
        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $success = $stmt->execute();
        if (!$success) {
            throw new \RuntimeException(sprintf('Error executing query "%s"', $query));
        }
    }

    protected function prepareStatement($db, $query)
    {
        try {
            $stmt = $db->prepare($query);
        } catch (\Exception $e) {
            $stmt = false;
        }

        if (false === $stmt) {
            throw new \RuntimeException('The database cannot successfully prepare the statement');
        }

        return $stmt;
    }

    protected function fetch($db, $query, array $args = array())
    {
        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();
        $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $return;
    }

    protected function close($db)
    {
    }

    /**
     * Returns whether data for the given token already exists in storage.
     *
     * @param string $token The profile token
     *
     * @return string
     */
    protected function has($token)
    {
        $db = $this->initDb();
        $tokenExists = $this->fetch($db, 'SELECT 1 FROM sf_profiler_data WHERE token = :token LIMIT 1', array(':token' => $token));
        $this->close($db);

        return !empty($tokenExists);
    }
}
