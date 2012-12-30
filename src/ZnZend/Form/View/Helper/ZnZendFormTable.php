<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-12-29T16:00+08:00
 */
namespace ZnZend\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\Form;
use Zend\Form\View\Helper\FormCollection;
use ZnZend\Form\View\Helper\ZnZendFormRow;

/**
 * Extension to FormCollection view helper to render form as a 2-column table
 *
 * The 1st column will contain the label while the 2nd column contains the
 * element and its errors if any. CSS classes can be applied on the table,
 * tr and td tags using provided methods
 */
class ZnZendFormTable extends FormCollection
{
    /**
     * The name of the default view helper that is used to render sub elements.
     *
     * Replaces default of 'formrow' in FormCollection
     *
     * @var string
     */
    protected $defaultElementHelper = 'znZendFormRow';

    /**
     * Form helper
     *
     * @var Form
     */
    protected $formHelper;

    /**
     * CSS class for <table>
     */
    protected $tableClass = '';

    /**
     * CSS class for <td> cell containing the element and its errors
     *
     * @var string
     */
    protected $tdElementClass = '';

    /**
     * Rendering format for <td> cell containing the element and its errors
     *
     * @see ZnZendFormRow::setRenderFormat() for use of placeholders
     * @var string
     */
    protected $tdElementFormat = '%element%%errors%';

    /**
     * CSS class for <td> cell containing the label
     *
     * @var string
     */
    protected $tdLabelClass = '';

    /**
     * Rendering format for <td> cell containing the label
     *
     * @see ZnZendFormRow::setRenderFormat() for use of placeholders
     * @var string
     */
    protected $tdLabelFormat = '%labelOpen%%label%%labelClose%';

    /**
     * CSS class for each <tr> row
     *
     * @var string
     */
    protected $trClass = '';

    /**
     * Retrieve the Form helper
     *
     * @return Form
     */
    protected function getFormHelper()
    {
        if ($this->formHelper) {
            return $this->formHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->formHelper = $this->view->plugin('form');
        }

        if (!$this->formHelper instanceof Form) {
            $this->formHelper = new Form();
        }

        return $this->formHelper;
    }

    /**
     * Retrieve rendering format for each table row
     *
     * @return string
     */
    public function getRowFormat()
    {
        return sprintf(
              '    <tr class="%s">' . PHP_EOL
            . '      <td class="%s">%s</td>' . PHP_EOL
            . '      <td class="%s">' . PHP_EOL
            . '        %s' . PHP_EOL
            . '      </td>' . PHP_EOL
            . '    </tr>' . PHP_EOL,
            $this->getTrClass(),
            $this->getTdLabelClass(),
            $this->getTdLabelFormat(),
            $this->getTdElementClass(),
            $this->getTdElementFormat()
        );
    }

    /**
     * Retrieve CSS class for <table>
     *
     * @var string
     */
    public function getTableClass()
    {
        return $this->tableClass;
    }

    /**
     * Retrieve CSS class for <td> cell containing the element and its errors
     *
     * @var string
     */
    public function getTdElementClass()
    {
        return $this->tdElementClass;
    }

    /**
     * Retrieve rendering format for <td> cell containing the element and its errors
     *
     * @var string
     */
    public function getTdElementFormat()
    {
        return $this->tdElementFormat;
    }

    /**
     * Retrieve CSS class for <td> cell containing the label
     *
     * @var string
     */
    public function getTdLabelClass()
    {
        return $this->tdLabelClass;
    }

    /**
     * Retrieve rendering format for <td> cell containing the label
     *
     * @var string
     */
    public function getTdLabelFormat()
    {
        return $this->tdLabelFormat;
    }

    /**
     * Retrieve CSS class for <tr> row
     *
     * @var string
     */
    public function getTrClass()
    {
        return $this->trClass;
    }

    /**
     * Render a collection by iterating through all fieldsets and elements
     *
     * Wraps markup in <table> and form open/close tags
     *
     * @see    FormCollection::render()
     * @param  ElementInterface $element
     * @return string
     */
    public function render(ElementInterface $element)
    {
        // Cannot instantiate helper the normal way, ie. new ZnZendFormRow()
        // as the view needs to be injected in. Need to use $this->view->plugin('helperName')
        $elementHelper = $this->getElementHelper();
        $elementHelper->setRenderFormat($this->getRowFormat());
        $this->setElementHelper($elementHelper);

        $elementMarkup = parent::render($element);
        $markup = sprintf(
            '%s%s%s%s%s',
            $this->getFormHelper()->openTag($element) . PHP_EOL,
            '  <table class="' . $this->getTableClass() . '">' . PHP_EOL,
            $elementMarkup,
            '  </table>' . PHP_EOL,
            $this->getFormHelper()->closeTag($element) . PHP_EOL
        );

        return $markup;
    }

    /**
     * Set CSS class for <table>
     *
     * @param  string $cssClass
     * @return ZnZendFormTable
     */
    public function setTableClass($cssClass)
    {
        $this->tableClass = $cssClass;
        return $this;
    }

    /**
     * Set CSS class for <td> cell containing the element and its errors
     *
     * @param  string $cssClass
     * @return ZnZendFormTable
     */
    public function setTdElementClass($cssClass)
    {
        $this->tdElementClass = $cssClass;
        return $this;
    }

    /**
     * Set CSS class for <td> cell containing the label
     *
     * @param  string $cssClass
     * @return ZnZendFormTable
     */
    public function setTdLabelClass($cssClass)
    {
        $this->tdLabelClass = $cssClass;
        return $this;
    }

    /**
     * Set CSS class for <tr> row
     *
     * @param  string $cssClass
     * @return ZnZendFormTable
     */
    public function setTrClass($cssClass)
    {
        $this->trClass = $cssClass;
        return $this;
    }
}
