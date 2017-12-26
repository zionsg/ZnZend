<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormElement as BaseFormElement;
use ZnZend\Form\Element;

/**
 * Adds on to Zend formElement helper to handle rendering for ZnZend form elements
 */
class ZnZendFormElement extends BaseFormElement
{
    /**
     * Render an element
     *
     * Introspects the element type and attributes to determine which
     * helper to utilize when rendering.
     *
     * @param  ElementInterface $element
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $renderer = $this->getView();
        if (!method_exists($renderer, 'plugin')) {
            // Bail early if renderer is not pluggable
            return '';
        }

        if ($element instanceof Element\Value) {
            $helper = $renderer->plugin('znZendFormValue');
            return $helper($element);
        }

        return parent::render($element);
    }
}
