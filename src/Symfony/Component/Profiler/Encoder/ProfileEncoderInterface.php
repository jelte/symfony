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
 * ProfileEncoderInterface.
 *
 * Encode/Decode Profiles so they can be easily stored and retrieved.
 */
interface ProfileEncoderInterface
{
    /**
     * Decodes data to a Profile.
     *
     * @param array $data
     *
     * @return ProfileInterface
     */
    public function decode(array $data);

    /**
     * Encodes a ProfileInterface into an array.
     *
     * @param ProfileInterface $profile
     *
     * @return array
     */
    public function encode(ProfileInterface $profile);

    /**
     * Returns whether this class supports the given profile.
     *
     * @param $class string Name of the class.
     *
     * @return bool
     */
    public function supports($class);

    /**
     * Returns the keys which needs to be used as indexes.
     *
     * @return array
     */
    public function getIndexes();
}
