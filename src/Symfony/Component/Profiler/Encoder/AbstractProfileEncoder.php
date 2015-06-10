<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Encoder;

use Symfony\Component\Profiler\ProfileInterface;

/**
 * Class AbstractProfileEncoder.
 */
abstract class AbstractProfileEncoder
{
    /**
     * {@inheritdoc}
     */
    protected function doesSupports($className, $expectedClass)
    {
        if ($expectedClass !== $className) {
            $class = new \ReflectionClass($className);
            if (
                !in_array($className, $class->getInterfaceNames())
                && !$class->isSubclassOf($expectedClass)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEncode(ProfileInterface $profile)
    {
        $data = array(
            '_class' => get_class($profile),
            'token' => $profile->getToken(),
            'parent_token' => $profile->getParentToken(),
            'children' => base64_encode(serialize(array_map(function (ProfileInterface $p) { return $p->getToken(); }, $profile->getChildren()))),
            'time' => $profile->getTime(),
            'data' => base64_encode(serialize($profile->getData())),
        );
        if (count($profile->getCollectors()) > 0) {
            $data['collectors'] = base64_encode(serialize($profile->getCollectors()));
        }

        return $data;
    }

    /**
     * Enrich the profile with additional information.
     *
     * @param ProfileInterface $profile
     * @param array            $data
     *
     * @return array
     */
    protected function doDecode(ProfileInterface $profile, array $data)
    {
        $profile->setData(unserialize(base64_decode($data['data'])));
        if (isset($data['collectors'])) {
            $profile->setCollectors(unserialize(base64_decode($data['collectors'])));
        }

        return $profile;
    }

    /**
     * Returns the keys which needs to be used as indexes.
     *
     * @return array
     */
    protected function getIndexes()
    {
        return array(
            'token',
            'parent_token',
            'time',
        );
    }
}
