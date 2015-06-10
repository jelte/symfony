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
use Symfony\Component\Profiler\ConsoleProfile;

/**
 * ConsoleProfileEncoder.
 *
 * Encode/Decode ConsoleProfiles so they can be easily stored and retrieved.
 */
class ConsoleProfileEncoder extends AbstractProfileEncoder implements ProfileEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        return parent::doesSupports($className, 'Symfony\Component\Profiler\ConsoleProfile');
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ProfileInterface $profile)
    {
        /* @var ConsoleProfile $profile */
        $data = array(
            'command' => $profile->getCommand(),
            'options' => base64_encode(serialize($profile->getOptions())),
            'arguments' => base64_encode(serialize($profile->getArguments())),
            'exit_code' => $profile->getExitCode(),
        );

        return array_merge(parent::doEncode($profile), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $data)
    {
        $profile = new ConsoleProfile(
            $data['token'],
            $data['command'],
            unserialize(base64_decode($data['arguments'])),
            unserialize(base64_decode($data['options'])),
            $data['exit_code'],
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
            'command',
            'exit_code',
        ));
    }
}
