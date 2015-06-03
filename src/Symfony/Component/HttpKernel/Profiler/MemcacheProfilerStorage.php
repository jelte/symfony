<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Profiler;

use Symfony\Component\Profiler\Storage\MemcacheProfilerStorage as BaseMemcacheProfilerStorage;

/**
 * Memcache Profiler Storage.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 * 
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\Storage\MemcacheProfilerStorage instead.
 */
class MemcacheProfilerStorage extends BaseMemcacheProfilerStorage
{
    protected function createProfileFromData($token, $data, $parent = null)
    {
        $profile = new Profile($token);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setCollectors($data['collectors']);
        $profile->setCollectors($data['data']);

        if (!$parent && $data['parent']) {
            $parent = $this->read($data['parent']);
        }

        if ($parent) {
            $profile->setParent($parent);
        }

        foreach ($data['children'] as $token) {
            if (!$token) {
                continue;
            }

            if (!$childProfileData = $this->getValue($this->getItemName($token))) {
                continue;
            }

            $profile->addChild($this->createProfileFromData($token, $childProfileData, $profile));
        }

        return $profile;
    }
}
