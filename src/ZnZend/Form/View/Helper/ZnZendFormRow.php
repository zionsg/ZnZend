<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-12-29T15:00+08:00
 */
namespace ZnZend\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormRow;

/**
 * Extension of FormRow view helper to allow rendering format to be customized
 */
class ZnZendFormRow extends FormRow
{
    /**
     * Rendering format for label, element and errors
     *
     * @var string
     */
    protected $renderFormat;

    /**
     * Get rendering format
     *
     * If not set, attempts to follow format in FormRow
     *
     * @return string
     */
    public function getRenderFormat()
    {
        if (null !== $this->renderFormat) {
            return $this->renderFormat;
        }

        switch ($this->labelPosition) {
            case self::LABEL_PREPEND:
                $this->renderFormat = '%labelOpen%%label%%element%%labelClose%%errors%';
                break;
            case self::LABEL_APPEND:
            default:
                $this->renderFormat = '%labelOpen%%element%%label%%labelClose%%errors%';
                break;
        }

        return $this->renderFormat;
    }

    /**
     * Utility form helper that renders a label (if it exists), an element and errors
     *
     * Bulk of the code is from FormRow. The rendering format is applied at the end
     * If the label, element and errors are empty, the format is ignored and an empty string is returned
     *
     * @param ElementInterface $element
     * @return string
     * @throws \Zend\Form\Exception\DomainException
     */
    public function render(ElementInterface $element)
    {
        $escapeHtmlHelper    = $this->getEscapeHtmlHelper();
        $labelHelper         = $this->getLabelHelper();
        $elementHelper       = $this->getElementHelper();
        $elementErrorsHelper = $this->getElementErrorsHelper();

        $label           = $element->getLabel();
        $inputErrorClass = $this->getInputErrorClass();
        $elementErrors   = $elementErrorsHelper->render($element);

        // Does this element have errors ?
        if (!empty($elementErrors) && !empty($inputErrorClass)) {
            $classAttributes = ($element->hasAttribute('class') ? $element->getAttribute('class') . ' ' : '');
            $classAttributes = $classAttributes . $inputErrorClass;

            $element->setAttribute('class', $classAttributes);
        }

        $elementString = $elementHelper->render($element);

        if (isset($label) && '' !== $label) {
            // Translate the label
            if (null !== ($translator = $this->getTranslator())) {
                $label = $translator->translate(
                    $label, $this->getTranslatorTextDomain()
                );
            }

            $label = $escapeHtmlHelper($label);
            $labelAttributes = $element->getLabelAttributes();

            if (empty($labelAttributes)) {
                $labelAttributes = $this->labelAttributes;
            }

            // Multicheckbox elements have to be handled differently as the HTML standard does not allow nested
            // labels. The semantic way is to group them inside a fieldset
            $type = $element->getAttribute('type');
            if ($type === 'multi_checkbox' || $type === 'radio') {
                $markup = sprintf(
                    '<fieldset><legend>%s</legend>%s</fieldset>',
                    $label,
                    $elementString);
            } else {
                if ($element->hasAttribute('id')) {
                    $labelOpen = $labelHelper($element);
                    $labelClose = '';
                    $label = '';
                } else {
                    $labelOpen  = $labelHelper->openTag($labelAttributes);
                    $labelClose = $labelHelper->closeTag();
                }

                if ($label !== '') {
                    $label = '<span>' . $label . '</span>';
                }
            }

            if (!$this->renderErrors) {
                $elementErrors = '';
            }
        } else {
            $labelOpen = '';
            $label = '';
            $labelClose = '';
            if (!$this->renderErrors) {
                $elementErrors = '';
            }
        }

        if (empty($label) && empty($elementString) && empty($elementErrors)) {
            return '';
        }

        // Apply render format
        $markup = str_replace(
            array('%labelOpen%', '%label%', '%labelClose%', '%element%', '%errors%'),
            array($labelOpen, $label, $labelClose, $elementString, $elementErrors),
            $this->getRenderFormat()
        );

        return $markup;
    }

    /**
     * Set rendering format
     *
     * Placeholders that can be used in format string:
     *   %labelOpen%
     *   %label%
     *   %labelClose%
     *   %element%
     *   %errors%
     *
     * @param  string  $renderFormat
     * @return ZnZendFormRow
     */
    public function setRenderFormat($renderFormat)
    {
        $this->renderFormat = $renderFormat;
        return $this;
    }
}