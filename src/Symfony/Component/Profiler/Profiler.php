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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface as DeprecatedDataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface as DeprecatedLateDataCollectorInterface;
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
class Profiler
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
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(ProfilerStorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function generateToken()
    {
        return substr(hash('sha256', uniqid(mt_rand(), true)), 0, 6);
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
     * @return ProfileInterface A Profile instance
     */
    public function load($token)
    {
        return $this->storage->read($token);
    }

    /**
     * Loads the Profile for the given Response.
     *
     * @param Response $response A Response instance
     *
     * @return ProfileInterface A Profile instance
     */
    public function loadFromResponse(Response $response)
    {
        if (!$token = $response->headers->get('X-Debug-Token')) {
            return false;
        }

        return $this->load($token);
    }

    /**
     * Saves a Profile.
     *
     * @param ProfileInterface $profile A Profile instance
     *
     * @return bool
     */
    public function save(ProfileInterface $profile)
    {
        // late collect
        foreach ($profile->getCollectors() as $collector) {
            if ($collector instanceof LateDataCollectorInterface) {
                $profile->add($collector->getName(), $collector->lateCollect());
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
     * @param ProfileInterface $profile A Profile instance
     *
     * @return string The exported data
     */
    public function export(ProfileInterface $profile)
    {
        return base64_encode(serialize($profile));
    }

    /**
     * Imports data into the profiler storage.
     *
     * @param string $data A data string as exported by the export() method
     *
     * @return ProfileInterface A Profile instance
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
     * @param array $criteria The Criteria
     * @param string $limit The maximum number of tokens to return
     * @param string $start The start date to search from
     * @param string $end The end date to search to
     *
     * @return array An array of tokens
     *
     * @see http://php.net/manual/en/datetime.formats.php for the supported date/time formats
     */
    public function findBy(array $criteria, $limit, $start, $end)
    {
        return $this->storage->findBy($criteria, $limit, $this->getTimestamp($start), $this->getTimestamp($end));
    }

    /**
     * Finds profiler tokens for the given criteria.
     *
     * @param string $ip The IP
     * @param string $url The URL
     * @param string $limit The maximum number of tokens to return
     * @param string $method The request method
     * @param string $start The start date to search from
     * @param string $end The end date to search to
     *
     * @return array An array of tokens
     *
     * @see http://php.net/manual/en/datetime.formats.php for the supported date/time formats
     * @deprecated since 2.8 and will be removed in 3.0 use Profiler::findBy instead
     */
    public function find($ip, $url, $limit, $method, $start, $end)
    {
        $criteria = array();
        if (!empty($ip)) {
            $criteria['ip'] = $ip;
        }
        if (!empty($url)) {
            $criteria['url'] = $url;
        }
        if (!empty($method)) {
            $criteria['method'] = $method;
        }

        return $this->findBy($criteria, $limit, $start, $end);
    }

    public function profileRequest(Request $request, Response $response)
    {
        if (!$this->enabled) {
            return;
        }

        $profile = new HttpProfile(
            $this->generateToken(),
            $request->getClientIp(),
            $request->getUri(),
            $request->getMethod(),
            $response->getStatusCode()
        );

        $response->headers->set('X-Debug-Token', $profile->getToken());

        return $this->profile($profile, $request, $response);
    }

    public function profileCommand(Command $command, InputInterface $input, $exitCode)
    {
        if (!$this->enabled) {
            return;
        }

        $profile = new ConsoleProfile($this->generateToken(), $command->getName(), $input->getArguments(), $input->getOptions(), $exitCode);

        return $this->profile($profile);
    }

    /**
     * Collects data.
     *
     * @param ProfileInterface $profile
     *
     * @return ProfileInterface
     */
    public function profile(ProfileInterface $profile, Request $request = null, Response $response = null, \Exception $exception = null)
    {
        foreach ($this->collectors as $collector) {
            $collector->setToken($profile->getToken());
            if ($collector instanceof RuntimeDataCollectorInterface) {
                $profile->add($collector->getName(), $collector->collect());
            } elseif ($collector instanceof LateDataCollectorInterface) {
                // we need to clone for sub-requests
                $profile->addCollector(clone $collector);
            } elseif ($collector instanceof DeprecatedDataCollectorInterface) {
                @trigger_error(sprintf("%s implementing %s will is deprecated since version 2.8 and support for it will be removed in 3.0.", get_class($collector), 'Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface'), E_USER_DEPRECATED);
                /** @var DeprecatedDataCollectorInterface $clone */
                $clone = clone $collector;
                if (!($clone instanceof DeprecatedLateDataCollectorInterface)) {
                    if (null !== $request && null !== $response) {
                        $clone->collect($request, $response, $exception);
                        // we need to clone for sub-requests
                        $profile->addCollector($clone);
                    }
                } else {
                    // we need to clone for sub-requests
                    $profile->addCollector($clone);
                }
            }
        }

        return $profile;
    }

    private function getTimestamp($value)
    {
        if (null === $value || '' == $value) {
            return;
        }

        try {
            $value = new \DateTime(is_numeric($value) ? '@' . $value : $value);
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
