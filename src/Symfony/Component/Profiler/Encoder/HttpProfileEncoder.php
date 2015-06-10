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
use Symfony\Component\Profiler\HttpProfile;

/**
 * HttpProfileEncoder.
 *
 * Encode/Decode HttpProfiles so they can be easily stored and retrieved.
 */
class HttpProfileEncoder extends AbstractProfileEncoder implements ProfileEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        return parent::doesSupports($className, 'Symfony\Component\Profiler\HttpProfile');
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ProfileInterface $profile)
    {
        /* @var HttpProfile $profile */
        $data = array(
            'ip' => $profile->getIp(),
            'url' => $profile->getUrl(),
            'method' => $profile->getMethod(),
            'status_code' => $profile->getStatusCode(),
        );

        return array_merge(parent::doEncode($profile), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $data)
    {
        $profile = new HttpProfile(
            $data['token'],
            isset($data['ip']) ? $data['ip'] : null,
            isset($data['url']) ? $data['url'] : null,
            isset($data['method']) ? $data['method'] : null,
            isset($data['status_code']) ? $data['status_code'] : null,
            $data['time']
        );

        return parent::doDecode($profile, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexes()
    {
        return array_merge(parent::getIndexes(), array(
            'ip',
            'url',
            'method',
            'status_code',
        ));
    }
}
