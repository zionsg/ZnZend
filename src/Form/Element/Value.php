<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form\Element;

use Zend\Form\Element as BaseFormElement;

/**
 * Element for displaying value only without <input>
 */
class Value extends BaseFormElement
{
    protected $attributes = array(
        'type' => 'value',
    );
}
