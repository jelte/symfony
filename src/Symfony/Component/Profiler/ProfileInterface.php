<?php

namespace Symfony\Component\Profiler;

use Symfony\Component\Profiler\DataCollector\DataCollectorInterface;
use Symfony\Component\Profiler\ProfileData\ProfileDataInterface;

interface ProfileInterface
{
    public function getToken();

    public function setParent(ProfileInterface $parent);
    public function getParent();

    public function getParentToken();

    public function getChildren();

    public function getTime();
    public function setChildren(array $children);

    public function addChild(ProfileInterface $child);

    public function getCollectors();

    /**
     * Assign a collection of collectors to the profile.
     * This method is only to provide BC for DataCollectors prior to 2.8.
     *
     * @param array $collectors
     *
     * @deprecated since 2.8. Will be removed in 3.1.
     */
    public function setCollectors(array $collectors);
    public function addCollector(DataCollectorInterface $collector);
    public function removeCollector($name);

    public function getData();
    public function setData(array $data);

    public function add($name, ProfileDataInterface $profileData);
    public function get($name);
    public function has($name);
}
