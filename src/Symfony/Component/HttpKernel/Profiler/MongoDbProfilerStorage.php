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

use Symfony\Component\Profiler\Storage\MongoDbProfilerStorage as BaseMongoDbProfilerStorage;

/**
 * Class MongoDbProfilerStorage.
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Symfony\Component\Profiler\Storage\MongoDbProfilerStorage instead.
 */
class MongoDbProfilerStorage extends BaseMongoDbProfilerStorage
{
    /**
     * @param array $data
     *
     * @return Profile
     */
    protected function getProfile(array $data)
    {
        $profile = new Profile($data['token']);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setCollectors(unserialize(base64_decode($data['collectors'])));
        $profile->setData(unserialize(base64_decode($data['data'])));

        return $profile;
    }
}
