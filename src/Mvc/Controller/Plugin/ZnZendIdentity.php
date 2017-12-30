<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\Plugin\Identity as ZendIdentityPlugin;
use ZnZend\Authentication\Identity;
use ZnZend\Authentication\IdentityInterface;
use ZnZend\Mvc\Exception;
use ZnZend\Permissions\Acl\Role\GenericRole;

/**
 * Controller plugin to fetch the authenticated identity as an instance of IdentityInterface
 * and its role as an instance of RoleInterface
 *
 * When invoked, its factory Service\ZnZendIdentityFactory will look for a service
 * by the name `ZnZend\Authentication\AuthenticationService` in the `ServiceManager`, similar
 * to the Zend Identity controller plugin. The service does not exist but defaults to
 * `Zend\Authentication\AuthenticationService`.
 */
class ZnZendIdentity extends ZendIdentityPlugin
{
    /**
     * Retrieve the current identity, if any.
     *
     * Proxies to getIdentity() with the option to set the authentication service.
     *
     * @param  AuthenticationService $authService
     * @return null|IdentityInterface
     * @throws Exception\RuntimeException
     */
    public function __invoke(AuthenticationService $authService = null)
    {
        if ($authService !== null) {
            $this->setAuthenticationService($authService);
        }
        return $this->getIdentity();
    }

    /**
     * Retrieve the current identity, if any.
     *
     * If none is present, returns null.
     * If identity does not implement IdentityInterface, new instance of Identity
     * is created with sane defaults and GenericRole.
     *
     * @return null|IdentityInterface
     * @throws Exception\RuntimeException
     */
    public function getIdentity()
    {
        if (! $this->authenticationService instanceof AuthenticationService) {
            throw new Exception\RuntimeException('No AuthenticationService instance provided');
        }
        if (! $this->authenticationService->hasIdentity()) {
            return null;
        }

        $currentIdentity = $this->authenticationService->getIdentity();
        if ($currentIdentity instanceof IdentityInterface) {
            return $currentIdentity;
        }

        $identity = new Identity();
        $name = (string) $currentIdentity;
        $identity->setIdentityId($name)
                 ->setUsername($name)
                 ->setDisplayName($name)
                 ->addRole(new GenericRole());

        return $identity;
    }

    /**
     * Retrieve role of the current identity if any or return default role
     *
     * @param  string $defaultRole Optional name of default role if there is no authenticated identity or it has no role
     * @return RoleInterface
     */
    public function getRole($defaultRole = null)
    {
        $identity = $this->getIdentity();
        $role = ($identity ? $identity->getRole() : null);

        return ($role !== null) ? $role : new GenericRole($defaultRole);
    }
}
