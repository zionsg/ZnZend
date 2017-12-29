<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Authentication;

use ZnZend\Permissions\Acl\Role\RoleInterface;

/**
 * Interface for identity stored in authentication service
 *
 * This facilitates generic treatment of users in a Content Management System.
 * Of particular interest is the handling of roles to make integration with acl
 * easier, eg. $role = $auth->getIdentity()->getRole().
 */
interface IdentityInterface
{
    /**
     * Value when identity is treated as a string
     *
     * This is useful when a generically coded application uses the identity
     * in auditing logs and has no knowledge of the interface.
     * The value should usually default to the username.
     *
     * @return string
     */
    public function __toString();

    /**
     * Set identity record id
     *
     * This is mostly applicable to users retrieved from a database.
     * This is not named setId() to prevent collision with ZnZend\Db\EntityInterface.
     *
     * @param  mixed $id
     * @return IdentityInterface
     */
    public function setIdentityId($id);

    /**
     * Get identity record id
     *
     * @return mixed
     */
    public function getIdentityId();

    /**
     * Set username
     *
     * Username refers to the login userid of the user.
     *
     * @param  string $username
     * @return IdentityInterface
     */
    public function setUsername($username);

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Set display name
     *
     * This is the name displayed in the application, which may not always be the username.
     *
     * @param  string $displayName
     * @return IdentityInterface
     */
    public function setDisplayName($displayName);

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName();

    /**
     * Add role
     *
     * @param  RoleInterace $role
     * @return IdentityInterface
     */
    public function addRole(RoleInterface $role);

    /**
     * Get roles
     *
     * @return array of RoleInterface
     */
    public function getRoles();

    /**
     * Get role
     *
     * In the scenario where the user has multiple roles, this should return the highest ranking role.
     *
     * @return null|RoleInterface Return null if no role is present
     */
    public function getRole();
}
