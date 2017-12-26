<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Permissions\Acl\Role;

use Zend\Permissions\Acl\Role\GenericRole as ZendGenericRole;
use ZnZend\Permissions\Acl\Role\RoleInterface;

/**
 * Generic role that defaults to 'guest' and where smaller numbers indicate higher role rank
 */
class GenericRole extends ZendGenericRole implements RoleInterface
{
    /**
     * Role rank
     *
     * @var int
     */
    protected $roleRank = PHP_INT_MAX;

    /**
     * Sets the Role identifier
     *
     * Defaults to 'guest'.
     *
     * @param string $roleId
     * @param int    $roleRank Role rank should only be set via constructor and not setter
     */
    public function __construct($roleId = 'guest', $roleRank = null)
    {
        parent::__construct($roleId);
        if ($roleRank !== null && is_numeric($roleRank)) {
            $this->roleRank = (int) $roleRank;
        }
    }

    /**
     * Defined by RoleInterface; Compares this role to another role
     *
     * Smaller numbers indicate higher role rank.
     * In a database, roles would be stored in a table with a numeric column to indicate role rank.
     * If larger numbers indicate higher role rank and the Root Administrator role which is ranked highest
     * has a value of 1, the number will have to be increased when new roles are added, hence this implementation.
     *
     * @param  RoleInterface $otherRole
     * @return int -1 if this role is ranked lower than $otherRole.
     *             0 if both roles are equally ranked.
     *             1 if this role is ranked higher than $otherRole.
     */
    public function compare(RoleInterface $otherRole)
    {
        $roleRank = $this->getRoleRank();
        $otherRoleRank = $otherRole->getRoleRank();

        if ($roleRank == $otherRoleRank) {
            return 0;
        }

        return (($roleRank < $otherRoleRank) ? 1 : -1);
    }

    /**
     * Defined by RoleInterface; Get role rank
     *
     * @return int
     */
    public function getRoleRank()
    {
        return $this->roleRank;
    }
}
