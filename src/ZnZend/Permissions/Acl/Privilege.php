<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Permissions\Acl;

use ReflectionClass;

/**
 * Class providing a standardized set of constants for Acl privileges
 *
 * Set is currently kept small at less than 10 constants, with the names kept within 8 characters each.
 * These privileges are usually used in a Content Management System (CMS).
 *
 * In general, lower-ranked roles should NOT be allowed to view/edit/delete the records of
 * higher-ranked roles, especially their user records. A user can at most create a new user with
 * a role ranked lower than his. In a blog CMS like WordPress, lower-ranked roles are not allowed to
 * publish/edit/delete the posts of higher-ranked roles.
 */
class Privilege
{
    /**
     * For adding/inserting of a new record
     *
     * Applies to a single record.
     */
    const ADD = 'add';

    /**
     * For editing/updating of an active record
     *
     * Applies to a single record or form field.
     * An inactive/deleted record must be undeleted before it can be edited.
     *
     * This privilege may be used in place of the "publish" privilege for blog posts,
     * by allowing/denying the editing of a "published" flag field.
     *
     * For sensitive fields such as credit card numbers, allowing of this privilege
     * will imply viewing of the actual unmasked value (so as to edit it).
     */
    const EDIT = 'edit';

    /**
     * For deleting/removing a record or marking it as deleted
     *
     * Applies to a single record.
     */
    const DELETE = 'delete';

    /**
     * For undeleting/restoring a record or marking it as active
     *
     * Applies to a single record.
     */
    const UNDELETE = 'undelete';

    /**
     * For listing active records
     *
     * Applies to a set of records.
     *
     * Not named LIST as it is a reserved keyword in PHP.
     */
    const LISTLIVE = 'listlive';

    /**
     * For listing of inactive/deleted records
     *
     * Applies to a set of records.
     */
    const LISTDEAD = 'listdead';

    /**
     * For viewing of active records or accessing/viewing of contents/details
     *
     * Applies to a single record, page, form field or fieldset.
     * This covers the scope of a VIEWLIVE constant which is not implemented to prevent confusion.
     */
    const VIEW = 'view';

    /**
     * For viewing of inactive/deleted records
     *
     * Applies to a single record.
     */
    const VIEWDEAD = 'viewdead';

    /**
     * For viewing part of a sensitive field such as credit card numbers which are usually encrypted
     *
     * Applies to a single form field or fieldset.
     *
     * If a role is only allowed to view partial credit card numbers, eg. XXXX XXXX XXXX 1234,
     * the "view" privilege must be denied on the credit card field and the "viewpart"
     * privilege allowed on the same field, else if the programmer forgets to
     * check the "viewpart" privilege, the credit card number will be shown in full.
     */
    const VIEWPART = 'viewpart';

    /**
     * Get list of class constants which can be used to populate a dropdown list
     *
     * @return array
     */
    public static function getConstants()
    {
        $reflection = new ReflectionClass(__CLASS__);
        return $reflection->getConstants();
    }
}
