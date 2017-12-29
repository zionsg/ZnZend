<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Permissions\Acl\Role;

use Zend\Permissions\Acl\Role\RoleInterface as ZendRoleInterface;

/**
 * Provides additional methods for comparing 2 roles.
 */
interface RoleInterface extends ZendRoleInterface
{
    /**
     * Value of role when treated as a string
     *
     * This is useful when a generically coded application uses the role in
     * auditing logs or displays it by simply echoing it with no knowledge of the inteface.
     *
     * @returns string
     */
    public function __toString();

    /**
     * Compares this role to another
     *
     * Comparisons should use this instead of getRoleRank() as the ranking system
     * is only known internally.
     *
     * @param  RoleInterface $otherRole
     * @return int -1 if this role is ranked lower than $otherRole.
     *             0 if both roles are equally ranked.
     *             1 if this role is ranked higher than $otherRole.
     */
    public function compare(RoleInterface $otherRole);

    /**
     * Get role rank
     *
     * @return int
     */
    public function getRoleRank();
}
