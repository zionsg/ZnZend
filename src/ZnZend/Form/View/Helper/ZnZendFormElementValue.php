<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form\View\Helper;

use Zend\Form\Element;
use Zend\Form\ElementInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * Render value of element without input for viewing only
 */
class ZnZendFormElementValue extends AbstractHelper
{
    /**
     * Invoke helper as function
     *
     * Proxies to {@link render()}.
     *
     * @param  ElementInterface|null $element
     * @return string|FormElement
     */
    public function __invoke(ElementInterface $element = null)
    {
        if (!$element) {
            return $this;
        }

        return $this->render($element);
    }

    /**
     * Render value of element
     *
     * Introspects the element type and its attributes to determine
     * how to render its value.
     *
     * @TODO Determine value to render for remaining elements that do not return string
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

        $value = $element->getValue();

        if ($element instanceof Element\Button) {
            return '';
        }

        if ($element instanceof Element\Captcha) {
            return '';
        }

        if ($element instanceof Element\Csrf) {
            return '';
        }

        if ($element instanceof Element\Collection) {
            $helper = $renderer->plugin('form_collection');
            return $helper($element);
        }

        if ($element instanceof Element\DateTimeSelect) {
            $helper = $renderer->plugin('form_date_time_select');
            return $helper($element);
        }

        if ($element instanceof Element\DateSelect) {
            $helper = $renderer->plugin('form_date_select');
            return $helper($element);
        }

        if ($element instanceof Element\MonthSelect) {
            $helper = $renderer->plugin('form_month_select');
            return $helper($element);
        }

        $type = $element->getAttribute('type');

        if ('checkbox' == $type) {
            return ($element->isChecked() ? 'yes' : 'no');
        }

        if ('color' == $type) {
            $helper = $renderer->plugin('form_color');
            return $helper($element);
        }

        if ('date' == $type) {
            $helper = $renderer->plugin('form_date');
            return $helper($element);
        }

        if ('datetime' == $type) {
            $helper = $renderer->plugin('form_date_time');
            return $helper($element);
        }

        if ('datetime-local' == $type) {
            $helper = $renderer->plugin('form_date_time_local');
            return $helper($element);
        }

        if ('email' == $type) {
            $helper = $renderer->plugin('form_email');
            return $helper($element);
        }

        if ('file' == $type) {
            $helper = $renderer->plugin('form_file');
            return $helper($element);
        }

        if ('hidden' == $type) {
            return '';
        }

        if ('image' == $type) {
            $helper = $renderer->plugin('form_image');
            return $helper($element);
        }

        if ('month' == $type) {
            $helper = $renderer->plugin('form_month');
            return $helper($element);
        }

        if ('multi_checkbox' == $type) {
            $helper = $renderer->plugin('form_multi_checkbox');
            return $helper($element);
        }

        if ('number' == $type) {
            $helper = $renderer->plugin('form_number');
            return $helper($element);
        }

        if ('password' == $type) {
            $helper = $renderer->plugin('form_password');
            return $helper($element);
        }

        if ('radio' == $type) {
            $helper = $renderer->plugin('form_radio');
            return $helper($element);
        }

        if ('range' == $type) {
            $helper = $renderer->plugin('form_range');
            return $helper($element);
        }

        if ('reset' == $type) {
            return '';
        }

        if ('search' == $type) {
            $helper = $renderer->plugin('form_search');
            return $helper($element);
        }

        if ('select' == $type) {
            return $value;
        }

        if ('submit' == $type) {
            return '';
        }

        if ('tel' == $type) {
            $helper = $renderer->plugin('form_tel');
            return $helper($element);
        }

        if ('text' == $type) {
            return $value;
            $helper = $renderer->plugin('form_text');
            return $helper($element);
        }

        if ('textarea' == $type) {
            return nl2br($value); // convert newlines to <br />
        }

        if ('time' == $type) {
            $helper = $renderer->plugin('form_time');
            return $helper($element);
        }

        if ('url' == $type) {
            return $value;
        }

        if ('week' == $type) {
            $helper = $renderer->plugin('form_week');
            return $helper($element);
        }

        return $value;
    }
}
