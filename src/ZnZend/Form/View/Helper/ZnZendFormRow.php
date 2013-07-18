<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\FormRow;
use ZnZend\Form\Exception;

/**
 * Extension of FormRow view helper to allow rendering format to be customized
 *
 * Avaliable placeholders for rendering format:
 *   %labelOpen%, %label%, %lableClose, %element%, %value%, %errors%
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
     * Set rendering format
     *
     * Placeholders that can be used in format string:
     *   %id%         => 'id' attribute for element
     *   %labelOpen%  => Opening tag for label
     *   %label%      => $element->getLabel()
     *   %labelClose% => Closing tag for label
     *   %element%    => Output from formElement helper
     *   %value%      => $element->getValue()
     *   %valueNl2br% => nl2br($element->getValue())
     *   %errors%     => Output from formElementErrors helper
     *
     * @param  string  $renderFormat
     * @return ZnZendFormRow
     */
    public function setRenderFormat($renderFormat)
    {
        $this->renderFormat = $renderFormat;
        return $this;
    }

    /**
     * Utility form helper that renders a label (if it exists), an element and errors
     *
     * Bulk of the code is from FormRow. The rendering format is applied at the end
     * If the label, element and errors are empty, the format is ignored and an empty string is returned
     *
     * @param  ElementInterface $element
     * @throws Exception\DomainException
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $escapeHtmlHelper    = $this->getEscapeHtmlHelper();
        $labelHelper         = $this->getLabelHelper();
        $elementHelper       = $this->getElementHelper();
        $elementErrorsHelper = $this->getElementErrorsHelper();

        $id              = $this->getId($element);
        $label           = $element->getLabel();
        $value           = $element->getValue();
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
            $labelAttributes['for'] = $id;

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
            array('%id%', '%labelOpen%', '%label%', '%labelClose%', '%element%', '%value%', '%valueNl2br%', '%errors%'),
            array($id, $labelOpen, $label, $labelClose, $elementString, $value, nl2br($value), $elementErrors),
            $this->getRenderFormat()
        );

        return $markup;
    }
}
