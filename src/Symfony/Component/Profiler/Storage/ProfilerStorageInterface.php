<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Profiler\Storage;

use Symfony\Component\Profiler\Profile;
use Symfony\Component\Profiler\ProfileInterface;

/**
 * ProfilerStorageInterface.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ProfilerStorageInterface
{
    /**
     * Finds profiler tokens for the given criteria.
     *
     * @param array    $criteria The criteria to find profiles
     * @param string   $limit    The maximum number of tokens to return
     * @param int|null $start    The start date to search from
     * @param int|null $end      The end date to search to
     *
     * @return array An array of tokens
     */
    public function findBy(array $criteria, $limit, $start = null, $end = null);

    /**
     * Reads data associated with the given token.
     *
     * The method returns false if the token does not exist in the storage.
     *
     * @param string $token A token
     *
     * @return Profile The profile associated with token
     */
    public function read($token);

    /**
     * Saves a Profile.
     *
     * @param ProfileInterface $profile A Profile instance
     *
     * @return bool Write operation successful
     */
    public function write(ProfileInterface $profile);

    /**
     * Purges all data from the database.
     */
    public function purge();
}
