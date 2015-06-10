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
 * Base Memcache storage for profiling information in a Memcache.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
abstract class AbstractMemcacheProfilerStorage extends AbstractProfilerStorage
{
    const TOKEN_PREFIX = 'sf_profiler_';

    protected $dsn;
    protected $lifetime;

    /**
     * Constructor.
     *
     * @param string $dsn      A data source name
     * @param int    $lifetime The lifetime to use for the purge
     */
    public function __construct($dsn, $lifetime = 86400)
    {
        $this->dsn = $dsn;
        $this->lifetime = (int) $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, $limit, $start = null, $end = null)
    {
        $indexName = $this->getIndexName();

        $indexContent = $this->getValue($indexName);
        if (!$indexContent) {
            return array();
        }

        $profileList = explode("\n", $indexContent);
        $result = array();

        foreach ($profileList as $item) {
            if ($limit === 0) {
                break;
            }

            if ($item == '') {
                continue;
            }

            $values = json_decode($item, true);
            $time = (int) $values['time'];

            if (!empty($start) && $time < $start) {
                continue;
            }

            if (!empty($end) && $time > $end) {
                continue;
            }

            if (!$this->validateCriteria($values, $criteria)) {
                continue;
            }

            $result[$values['token']] = $values;
            --$limit;
        }

        usort($result, function ($a, $b) {
            if ($a['time'] === $b['time']) {
                return 0;
            }

            return $a['time'] > $b['time'] ? -1 : 1;
        });

        return $result;
    }

    private function validateCriteria($values, $criteria)
    {
        foreach ($criteria as $key => $value) {
            if (false === strpos($values[$key], $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        // delete only items from index
        $indexName = $this->getIndexName();

        $indexContent = $this->getValue($indexName);

        if (!$indexContent) {
            return false;
        }

        $profileList = explode("\n", $indexContent);

        foreach ($profileList as $item) {
            if ($item == '') {
                continue;
            }

            if (false !== $pos = strpos($item, "\t")) {
                $this->delete($this->getItemName(substr($item, 0, $pos)));
            }
        }

        return $this->delete($indexName);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($token)
    {
        if (empty($token)) {
            return false;
        }

        return $this->getValue($this->getItemName($token));
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($token, array $data, array $indexedData)
    {
        $profileIndexed = false !== $this->getValue($this->getItemName($token));

        if ($this->setValue($this->getItemName($data['token']), $data, $this->lifetime)) {
            if (!$profileIndexed) {
                // Add to index
                $indexName = $this->getIndexName();

                $indexRow = json_encode($indexedData)."\n";

                return $this->appendValue($indexName, $indexRow, $this->lifetime);
            }

            return true;
        }

        return false;
    }

    /**
     * Retrieve item from the memcache server.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract protected function getValue($key);

    /**
     * Store an item on the memcache server under the specified key.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expiration
     *
     * @return bool
     */
    abstract protected function setValue($key, $value, $expiration = 0);

    /**
     * Delete item from the memcache server.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function delete($key);

    /**
     * Append data to an existing item on the memcache server.
     *
     * @param string $key
     * @param string $value
     * @param int    $expiration
     *
     * @return bool
     */
    abstract protected function appendValue($key, $value, $expiration = 0);

    /**
     * Get item name.
     *
     * @param string $token
     *
     * @return string|false
     */
    protected function getItemName($token)
    {
        $name = self::TOKEN_PREFIX.$token;

        if ($this->isItemNameValid($name)) {
            return $name;
        }

        return false;
    }

    /**
     * Get name of index.
     *
     * @return string|false
     */
    private function getIndexName()
    {
        $name = self::TOKEN_PREFIX.'index';

        if ($this->isItemNameValid($name)) {
            return $name;
        }

        return false;
    }

    private function isItemNameValid($name)
    {
        $length = strlen($name);

        if ($length > 250) {
            throw new \RuntimeException(sprintf('The memcache item key "%s" is too long (%s bytes). Allowed maximum size is 250 bytes.', $name, $length));
        }

        return true;
    }
}
