<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Permissions\Acl;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Resource;
use ZnZend\Permissions\Acl\Exception;

/**
 * Custom acl class
 *
 * Additions to ZendAcl:
 *   - addResourceRecursive() method created for adding resource ids that follow
 *     SNMP Object Id human-readable naming style, eg. root.parent.child
 */
class Acl extends ZendAcl
{
    /**
     * Adds a Resource and its parents recursively
     *
     * This is for adding resource ids that follow SNMP Object Id naming style, eg. root.parent.child
     * For a resource id such as 'root.parent.child', this does the following:
     *   addResource('root.parent.child', 'root.parent');
     *   addResource('root.parent', 'root');
     *
     * @param  Resource\ResourceInterface|string $resource
     * @throws Exception\InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function addResourceRecursive($resource)
    {
        if (is_string($resource)) {
            $resource = new Resource\GenericResource($resource);
        } elseif (!$resource instanceof Resource\ResourceInterface) {
            throw new Exception\InvalidArgumentException(
                'addResourceRecursive() expects $resource to be of type Zend\Permissions\Acl\Resource\ResourceInterface'
            );
        }

        $currentResources = $this->getResources();
        $resourceIds = explode('.', $resource->getResourceId());
        $count = count($resourceIds);

        $resourceId = $resourceIds[0];
        $parentResourceId = $resourceIds[0];

        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) {
                $resourceId .= '.' . $resourceIds[$i];
            }

            // Do not add resource if it exists, else Exception will be thrown
            if (in_array($resourceId, $currentResources)) {
                continue;
            }

            if (0 == $i) {
                // Root
                $this->addResource($resourceId);
            } else {
                $this->addResource($resourceId, $parentResourceId);
                $parentResourceId = $resourceId;
            }
        }

        return $this;
    }
}
