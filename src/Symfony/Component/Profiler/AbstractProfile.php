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
use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

/**
 * Profile.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractProfile implements ProfileInterface
{
    protected $token;
    protected $time;

    /**
     * @var DataCollectorInterface[]
     */
    protected $collectors = array();

    /**
     * @var ProfileDataInterface[]
     */
    private $data = array();

    /**
     * @var ProfileInterface
     */
    private $parent;

    /**
     * @var ProfileInterface[]
     */
    private $children = array();

    /**
     * Constructor.
     *
     * @param string $token The token
     * @param int    $time  The time
     */
    public function __construct($token, $time = null)
    {
        $this->token = $token;
        $this->time = null === $time ? time() : $time;
    }

    /**
     * Gets the token.
     *
     * @return string The token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the parent token.
     *
     * @param ProfileInterface $parent The parent Profile
     */
    public function setParent(ProfileInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent profile.
     *
     * @return ProfileInterface The parent profile
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the parent token.
     *
     * @return null|string The parent token
     */
    public function getParentToken()
    {
        return $this->parent ? $this->parent->getToken() : null;
    }

    /**
     * Returns the time.
     *
     * @return string The time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Finds children profilers.
     *
     * @return ProfileInterface[] An array of Profile
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets children profiler.
     *
     * @param ProfileInterface[] $children An array of Profile
     */
    public function setChildren(array $children)
    {
        $this->children = array();
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * Adds the child token.
     *
     * @param ProfileInterface $child The child Profile
     */
    public function addChild(ProfileInterface $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Gets the Collectors associated with this profile.
     *
     * @return DataCollectorInterface[]
     */
    public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Adds a Collector.
     *
     * @param DataCollectorInterface $collector A DataCollectorInterface instance
     */
    public function addCollector(DataCollectorInterface $collector)
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    /**
     * @param array $collectors
     *
     * @deprecated since 2.8. Will be removed in 3.0.
     */
    public function setCollectors(array $collectors)
    {
        foreach ($collectors as $collector) {
            $this->addCollector($collector);
        }
    }

    /**
     * @param $name
     *
     * @return DataCollectorInterface
     *
     * @deprecated since 2.8. Will be removed in 3.0.
     */
    public function getCollector($name)
    {
        return $this->get($name);
    }

    public function removeCollector($name)
    {
        unset($this->collectors[$name]);
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the ProfileData associated with this profile.
     *
     * @param ProfileDataInterface[] $data
     */
    public function setData(array $data)
    {
        $this->data = array();
        foreach ($data as $name => $profileData) {
            $this->add($name, $profileData);
        }
    }

    /**
     * Adds a Collector.
     *
     * $param $name name of the DataCollectorInterface instance
     *
     * @param ProfileDataInterface $profileData A ProfileDataInterface instance
     */
    public function add($name, ProfileDataInterface $profileData = null)
    {
        $this->data[$name] = $profileData;
    }

    /**
     * Retrieve data for a specific section.
     *
     * @param $name
     *
     * @return ProfileDataInterface
     */
    public function get($name)
    {
        if (!isset($this->data[$name])) {
            if (isset($this->collectors[$name])) {
                return $this->collectors[$name];
            }
            throw new \InvalidArgumentException(sprintf('ProfileData "%s" does not exist.', $name));
        }

        return $this->data[$name];
    }

    /**
     * Check of data exists for a specific section.
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->data[$name]) || isset($this->collectors[$name]);
    }
}
