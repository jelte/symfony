<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Profiler;

use Symfony\Component\Profiler\DataCollector\AbstractDataCollector;
use Symfony\Component\Profiler\DataCollector\RuntimeDataCollectorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * SecurityDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityDataCollector extends AbstractDataCollector implements RuntimeDataCollectorInterface
{
    private $tokenStorage;
    private $roleHierarchy;

    /**
     * Constructor.
     *
     * @param TokenStorageInterface|null  $tokenStorage
     * @param RoleHierarchyInterface|null $roleHierarchy
     */
    public function __construct(TokenStorageInterface $tokenStorage = null, RoleHierarchyInterface $roleHierarchy = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        return new SecurityProfileData($this->tokenStorage, $this->roleHierarchy);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'security';
    }
}
