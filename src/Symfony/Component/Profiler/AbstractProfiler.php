<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler;

use Symfony\Component\Profiler\DataCollector\DataCollectorInterface;
use Symfony\Component\Profiler\DataCollector\RuntimeDataCollectorInterface;
use Symfony\Component\Profiler\DataCollector\LateDataCollectorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Profiler\Storage\ProfilerStorageInterface;

/**
 * Profiler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jelte Steijaert <jelte@khepri.be>
 */
abstract class AbstractProfiler
{
    /**
     * @var DataCollectorInterface[]
     */
    private $collectors = array();

    /**
     * @var ProfilerStorageInterface
     */
    private $storage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * Constructor.
     *
     * @param ProfilerStorageInterface $storage A ProfilerStorageInterface instance
     * @param LoggerInterface          $logger  A LoggerInterface instance
     */
    public function __construct(ProfilerStorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Disables the profiler.
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables the profiler.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Loads the Profile for the given token.
     *
     * @param string $token A token
     *
     * @return Profile A Profile instance
     */
    public function load($token)
    {
        return $this->storage->read($token);
    }

    /**
     * Saves a Profile.
     *
     * @param Profile $profile A Profile instance
     *
     * @return bool
     */
    public function save(Profile $profile)
    {
        // late collect
        foreach ($profile->getCollectors() as $collector) {
            if ($collector instanceof LateDataCollectorInterface) {
                $profile->addProfileData($collector->getName(), $collector->lateCollect());
                $profile->removeCollector($collector->getName());
            }
        }

        if (!($ret = $this->storage->write($profile)) && null !== $this->logger) {
            $this->logger->warning('Unable to store the profiler information.', array('configured_storage' => get_class($this->storage)));
        }

        return $ret;
    }

    /**
     * Purges all data from the storage.
     */
    public function purge()
    {
        $this->storage->purge();
    }

    /**
     * Exports the current profiler data.
     *
     * @param Profile $profile A Profile instance
     *
     * @return string The exported data
     */
    public function export(Profile $profile)
    {
        return base64_encode(serialize($profile));
    }

    /**
     * Imports data into the profiler storage.
     *
     * @param string $data A data string as exported by the export() method
     *
     * @return Profile A Profile instance
     */
    public function import($data)
    {
        $profile = unserialize(base64_decode($data));

        if ($this->storage->read($profile->getToken())) {
            return false;
        }

        $this->save($profile);

        return $profile;
    }

    /**
     * Finds profiler tokens for the given criteria.
     *
     * @param string $ip     The IP
     * @param string $url    The URL
     * @param string $limit  The maximum number of tokens to return
     * @param string $method The request method
     * @param string $start  The start date to search from
     * @param string $end    The end date to search to
     *
     * @return array An array of tokens
     *
     * @see http://php.net/manual/en/datetime.formats.php for the supported date/time formats
     */
    public function find($ip, $url, $limit, $method, $start, $end)
    {
        return $this->storage->find($ip, $url, $limit, $method, $this->getTimestamp($start), $this->getTimestamp($end));
    }

    /**
     * Collects data.
     *
     * @return Profile|null A Profile instance or null if the profiler is disabled
     */
    public function profile()
    {
        if (false === $this->enabled) {
            return;
        }

        $profile = $this->createProfile();

        foreach ($this->collectors as $collector) {
            $collector->setToken($profile->getToken());
            if ( $collector instanceof RuntimeDataCollectorInterface ) {
                $profile->addProfileData($collector->getName(), $collector->collect());
            } else if ( $collector instanceof LateDataCollectorInterface ) {
                // we need to clone for sub-requests
                $profile->addCollector(clone $collector);
            }
        }

        return $profile;
    }

    /**
     * @return Profile
     */
    abstract protected function createProfile();

    private function getTimestamp($value)
    {
        if (null === $value || '' == $value) {
            return;
        }

        try {
            $value = new \DateTime(is_numeric($value) ? '@'.$value : $value);
        } catch (\Exception $e) {
            return;
        }

        return $value->getTimestamp();
    }

    /**
     * Gets the Collectors associated with this profiler.
     *
     * @return array An array of collectors
     */
    public function all()
    {
        return $this->collectors;
    }

    /**
     * Sets the Collectors associated with this profiler.
     *
     * @param DataCollectorInterface[] $collectors An array of collectors
     */
    public function set(array $collectors = array())
    {
        $this->collectors = array();
        foreach ($collectors as $collector) {
            $this->add($collector);
        }
    }

    /**
     * Adds a Collector.
     *
     * @param DataCollectorInterface $collector A DataCollectorInterface instance
     */
    public function add(DataCollectorInterface $collector)
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    /**
     * Returns true if a Collector for the given name exists.
     *
     * @param string $name A collector name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->collectors[$name]);
    }

    /**
     * Gets a Collector by name.
     *
     * @param string $name A collector name
     *
     * @return DataCollectorInterface A DataCollectorInterface instance
     *
     * @throws \InvalidArgumentException if the collector does not exist
     */
    public function get($name)
    {
        if (!isset($this->collectors[$name])) {
            throw new \InvalidArgumentException(sprintf('Collector "%s" does not exist.', $name));
        }

        return $this->collectors[$name];
    }
}
