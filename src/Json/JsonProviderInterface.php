<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Json;

/**
 * Interface to provide JSON representation of object
 *
 * @see Zend\Json::encode() which looks for a toJson() method in the object first
 *      before using json_encode()
 */
interface JsonProviderInterface
{
    /**
     * Return JSON representation of object
     *
     * json_encode() encodes all public properties of an object.
     * This method controls how the object is converted to JSON especially when
     * its properties are protected and accessed only via getters.
     * A subset of properties or additional info may be added in this way.
     *
     * @return string
     */
    public function toJson();
}
