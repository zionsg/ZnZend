<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-12-29T01:00+08:00
 */
namespace ZnZend\Form;

use Zend\Filter\StringTrim;
use Zend\Form\Element;
use Zend\Form\Form;
use Zend\Form\FormInterface;
use Zend\InputFilter\InputFilter;

/**
 * Base form class
 *
 * Additions to Zend_Form:
 *   - Params for dynamic elements can be injected via the constructor
 *     and retrieved with getParam()
 *   - init() method created for adding of elements
 *   - CSRF element is added by default
 *   - Validated data is trimmed upon retrieval
 */
abstract class AbstractForm extends Form
{
    /**
     * Params for use with dynamic elements
     *
     * @var array
     */
    protected $params = array();

    /**
     * Constructor
     *
     * Sample scenario for $params: An arbitrary number of text elements is to be created in
     * the form based on some condition determined in the controller. $params can
     * be used to pass in a set key with the value needed, eg. array('textElements' => 5).
     * Allowed file extensions and size limits can be passed in to file upload elements
     * via $params also.
     *
     * In view of the changed method signature, extending classes should add their
     * elements in the init() method lest they get the method signature wrong or forget
     * to call the parent constructor.
     *
     * @see    Form::__construct()
     * @param  null|int|string $name   Optional name for form
     * @param  array           $params Optional params for use with dynamic elements
     */
    public function __construct($name = null, array $params = array())
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post')
             ->setAttribute('enctype', 'multipart/form-data') // for file uploads
             ->setInputFilter(new InputFilter()); // default InputFilter

        // Add CSRF element
        $element = new Element\Csrf('token');
        $this->add($element);

        // Store params
        $this->params = $params;

        // Add elements for extending classes
        $this->init();

    } // end function __construct

    /**
     * Initialize form (used by extending classes)
     *
     * @return  void
     */
    public function init()
    {
    }

    /**
     * Map function recursively on array
     *
     * @param   callback $callback Callback function to map on array elements
     * @param   array    $array
     * @return  array
     * @throws  InvalidArgumentException If $callback is not callable
     */
    protected function array_map_recursive($callback, array $array)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid map callback provided');
        }

        $result   = array();
        $function = __FUNCTION__;

        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                $result[$key] = $callback($value);
            } else {
                $result[$key] = $this->$function($callback, $value);
            }
        }

        return $result;
    }

    /**
     * Retrieve the validated data
     *
     * By default, retrieves normalized values; pass one of the
     * FormInterface::VALUES_* constants to shape the behavior.
     *
     * If normalized values are retrieved, the StringTrim filter is applied
     * before returning values
     *
     * @see    Form::getData()
     * @param  int $flag
     * @return array|object
     * @throws Exception\DomainException
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);

        if (FormInterface::VALUES_NORMALIZED === $flag) {
            $filter = new StringTrim();
            $data = $this->array_map_recursive($filter, $data);
        }

        return $data;
    }

    /**
     * Retrieve stored param
     *
     * Not named get() due to FieldSet::get()
     *
     * @param  string $name    Name of param to retrieve
     * @param  mixed  $default Optional default value if param does not exist
     * @return null|mixed
     */
    public function getParam($name, $default = null)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return $default;
    }
}
