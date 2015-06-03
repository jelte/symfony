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

use Symfony\Component\Profiler\Storage\RedisProfilerStorage as BaseRedisProfilerStorage;

/**
 * RedisProfilerStorage stores profiling information in Redis.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 * @author Stephane PY <py.stephane1@gmail.com>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\Storage\RedisProfilerStorage instead.
 */
class RedisProfilerStorage extends BaseRedisProfilerStorage
{
    protected function createProfileFromData($token, $data, $parent = null)
    {
        $profile = new Profile($token);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setCollectors($data['collectors']);
        $profile->setData($data['data']);

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

            if (!$childProfileData = $this->getValue($this->getItemName($token), self::REDIS_SERIALIZER_PHP)) {
                continue;
            }

            $profile->addChild($this->createProfileFromData($token, $childProfileData, $profile));
        }

        return $profile;
    }
}
