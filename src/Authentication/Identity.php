<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Authentication;

use ZnZend\Authentication\IdentityInterface;
use ZnZend\Permissions\Acl\Role\RoleInterface;

/**
 * Class for identity stored in authentication service
 */
class Identity implements IdentityInterface
{
    /**
     * Identity record id
     *
     * @var mixed
     */
    protected $identityId;

    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Display name
     *
     * @var string
     */
    protected $displayName;

    /**
     * Roles
     *
     * @var array of RoleInterface
     */
    protected $roles = [];


    /**
     * Defined by IdentityInterface; Value when identity is treated as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * Defined by IdentityInterface; Set record id
     *
     * @param  mixed $id
     * @return IdentityInterface
     */
    public function setIdentityId($id)
    {
        $this->identityId = $id;
        return $this;
    }

    /**
     * Defined by IdentityInterface; Get record id
     *
     * @return mixed
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * Set username
     *
     * @param  string $username
     * @return IdentityInterface
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set display name
     *
     * @param  string $displayName
     * @return IdentityInterface
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Add role
     *
     * @param  RoleInterace $role
     * @return IdentityInterface
     */
    public function addRole(RoleInterface $role)
    {
        $this->roles[] = $role;
        return $this;
    }

    /**
     * Get roles
     *
     * @return array of RoleInterface
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get role
     *
     * @return null|RoleInterface Return null if no role is present
     */
    public function getRole()
    {
        if (empty($this->roles)) {
            return null;
        }

        if (1 == count($this->roles)) {
            return reset($this->roles);
        }

        $roles = $this->getRoles();
        usort($roles, function ($a, $b) {
            return $a->compare($b);
        });

        return reset($roles);
    }
}
