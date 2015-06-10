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

class MongoDbProfilerStorage extends AbstractProfilerStorage
{
    protected $dsn;
    protected $lifetime;
    private $mongo;

    /**
     * Constructor.
     *
     * @param string $dsn      A data source name
     * @param string $username Not used
     * @param string $password Not used
     * @param int    $lifetime The lifetime to use for the purge
     */
    public function __construct($dsn, $username = '', $password = '', $lifetime = 86400)
    {
        $this->dsn = $dsn;
        $this->lifetime = (int) $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, $limit, $start = null, $end = null)
    {
        $cursor = $this->getMongo()->find($this->buildQuery($criteria, $start, $end), array('_id', 'indexed'))->sort(array('indexed.time' => -1))->limit($limit);

        $tokens = array();
        foreach ($cursor as $profile) {
            $tokens[] = $profile['indexed'];
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $this->getMongo()->remove(array());
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($token)
    {
        $result = $this->getMongo()->findOne(array('_id' => $token, 'data' => array('$exists' => true)), array('data'));

        return $result['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($token, array $data, array $indexedData)
    {
        $this->cleanup();

        $values = array(
            '_id' => $token,
            'indexed' => $indexedData,
            'data' => array_filter($data, function ($v) { return !empty($v); }),
        );

        $result = $this->getMongo()->update(array('_id' => $token), $values, array('upsert' => true));

        return (bool) (isset($result['ok']) ? $result['ok'] : $result);
    }

    /**
     * Internal convenience method that returns the instance of the MongoDB Collection.
     *
     * @return \MongoCollection
     *
     * @throws \RuntimeException
     */
    protected function getMongo()
    {
        if (null !== $this->mongo) {
            return $this->mongo;
        }

        if (!$parsedDsn = $this->parseDsn($this->dsn)) {
            throw new \RuntimeException(sprintf('Please check your configuration. You are trying to use MongoDB with an invalid dsn "%s". The expected format is "mongodb://[user:pass@]host/database/collection"', $this->dsn));
        }

        list($server, $database, $collection) = $parsedDsn;
        $mongoClass = version_compare(phpversion('mongo'), '1.3.0', '<') ? '\Mongo' : '\MongoClient';
        $mongo = new $mongoClass($server);

        return $this->mongo = $mongo->selectCollection($database, $collection);
    }

    protected function cleanup()
    {
        $this->getMongo()->remove(array('indexed.time' => array('$lt' => time() - $this->lifetime)));
    }

    /**
     * @param array $criteria
     * @param int   $start
     * @param int   $end
     *
     * @return array
     */
    private function buildQuery(array $criteria, $start, $end)
    {
        $query = array();
        foreach ($criteria as $key => $value) {
            $query['indexed.'.$key] = $value;
        }

        if (!empty($start) || !empty($end)) {
            $query['indexed.time'] = array();
        }

        if (!empty($start)) {
            $query['indexed.time']['$gte'] = $start;
        }

        if (!empty($end)) {
            $query['indexed.time']['$lte'] = $end;
        }

        return $query;
    }

    /**
     * @param string $dsn
     *
     * @return null|array Array($server, $database, $collection)
     */
    private function parseDsn($dsn)
    {
        if (!preg_match('#^(mongodb://.*)/(.*)/(.*)$#', $dsn, $matches)) {
            return;
        }

        $server = $matches[1];
        $database = $matches[2];
        $collection = $matches[3];
        preg_match('#^mongodb://(([^:]+):?(.*)(?=@))?@?([^/]*)(.*)$#', $server, $matchesServer);

        if ('' == $matchesServer[5] && '' != $matches[2]) {
            $server .= '/'.$matches[2];
        }

        return array($server, $database, $collection);
    }
}
