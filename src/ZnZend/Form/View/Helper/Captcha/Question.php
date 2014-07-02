<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form\View\Helper\Captcha;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\Captcha\AbstractWord;
use ZnZend\Captcha\Question as CaptchaAdapter;
use ZnZend\Form\Exception;

/**
 * Helper for ZnZend\Captcha\Question adapter
 */
class Question extends AbstractWord
{
    /**
     * Render the captcha
     *
     * Allows setting of captcha class, position and separator via element options
     * as there is no other way to set it when using the formRow view helper.
     * Eg: echo $this->formRow()->render(
     *         $form->get('captcha')->setOptions(array(
     *             'captchaClass'    => 'img-responsive', // CSS class to apply to captcha image
     *             'captchaPosition' => 'append', // append input to captcha image
     *             'separator' => '<br>',
     *     )));
     *
     * @param  ElementInterface          $element
     * @throws Exception\DomainException
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $captcha = $element->getCaptcha();

        if ($captcha === null || !$captcha instanceof CaptchaAdapter) {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has a "captcha" attribute of type Zend\Captcha\Question; none found',
                __METHOD__
            ));
        }

        $captcha->generate();

        $imgAttributes = array(
            'src' => $captcha->getImage(),
        );

        if ($element->hasAttribute('id')) {
            $imgAttributes['id'] = $element->getAttribute('id') . '-image';
        }
        if ($element->hasAttribute('captchaClass')) {
            $imgAttributes['class'] = $element->getAttribute('captchaClass');
        }

        $closingBracket = $this->getInlineClosingBracket();
        $img = sprintf(
            '<img %s%s',
            $this->createAttributesString($imgAttributes),
            $closingBracket
        );

        $position     = $element->getOption('captchaPosition') ?: $this->getCaptchaPosition();
        $separator    = $element->getOption('separator') ?: $this->getSeparator();
        $captchaInput = $this->renderCaptchaInputs($element);

        $pattern = '%s%s%s';
        if ($position == self::CAPTCHA_PREPEND) {
            return sprintf($pattern, $captchaInput, $separator, $img);
        }

        return sprintf($pattern, $img, $separator, $captchaInput);
    }
}
