<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Permissions\Acl;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Resource;
use ZnZend\Permissions\Acl\Exception;

/**
 * Custom acl class
 *
 * Additions to ZendAcl:
 *   - addResource() method modified to recursively add resource ids that follow
 *     SNMP Object Id human-readable naming style, eg. root.parent.child
 */
class Acl extends ZendAcl
{
    /**
     * Defined by ZendAcl; Adds a Resource having an identifier unique to the ACL
     *
     * This recursively adds resource ids that follow SNMP Object Id naming style, eg. root.parent.child
     * Adding a resource id such as 'root.parent.child' will do the following:
     *   addResource('root.parent.child', 'root.parent');
     *   addResource('root.parent', 'root');
     *
     * $parent is ignored due to the inheritance laid out above.
     *
     * @param  Resource\ResourceInterface|string $resource
     * @param  Resource\ResourceInterface|string $parent
     * @throws Exception\InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function addResource($resource, $parent = null)
    {
        if (is_string($resource)) {
            $resource = new Resource\GenericResource($resource);
        } elseif (! $resource instanceof Resource\ResourceInterface) {
            throw new Exception\InvalidArgumentException(
                'addResource() expects $resource to be of type Zend\Permissions\Acl\Resource\ResourceInterface'
            );
        }
        if ($this->hasResource($resource->getResourceId())) {
            // Do not add resource if it exists, else Exception will be thrown
            return $this;
        }

        $currentResources = $this->getResources();
        $resourceIds = explode('.', $resource->getResourceId());
        $count = count($resourceIds);

        $resourceId = $resourceIds[0];
        $parentResourceId = null;

        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) {
                $resourceId .= '.' . $resourceIds[$i];
            }

            // Do not add resource if it exists, else Exception will be thrown
            if (in_array($resourceId, $currentResources)) {
                $parentResourceId = $resourceId; // remember to update parent!
                continue;
            }

            parent::addResource($resourceId, $parentResourceId);
            $parentResourceId = $resourceId;
        }

        return $this;
    }
}
