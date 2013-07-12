<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Form;

use Zend\Form\Element;
use Zend\Form\Form as ZendForm;
use Zend\InputFilter\InputFilter;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use ZnZend\Form\Exception;

/**
 * Base form class
 *
 * Additions to Zend_Form:
 *   - Params for dynamic elements can be injected via the constructor and retrieved with getParam()
 *   - init() method created for adding of elements in extending classes
 *   - CSRF element is added by default
 *   - Allows setting and getting of custom form-level error messages like in ZF1
 *   - Implements ResourceInterface allowing it to return resource id for itself or its elements
 *   - Allows setting of parent resource id which will be prefixed to its own resource id
 */
class Form extends ZendForm implements ResourceInterface
{
    /**
     * Params for use with dynamic elements
     *
     * @var array
     */
    protected $params = array();

    /**
     * Custom form-level error messages
     *
     * @var array
     */
    protected $errorMessages = array();

    /**
     * Resource id of form
     *
     * Defaults to class name of form with slashes replaced by underscores.
     *
     * @var string
     */
    protected $resourceId;

    /**
     * Resource id of form's parent
     *
     * A form's parent is usually the page where it is rendered - module.controller.action
     *
     * @var string
     */
    protected $parentResourceId;

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

    /**
     * Retrieve all stored params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Add param
     *
     * If a key exists, the new value will override the existing value.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Form
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Add params
     *
     * If a key exists, the new value will override the existing value.
     *
     * @param  array $params Key-value pairs
     * @return Form
     */
    public function addParams(array $params = array())
    {
        $this->params = array_merge(
            $this->params,
            $params
        );
        return $this;
    }

    /**
     * Checks if the form has errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        if ($this->hasValidated) {
            return $this->isValid;
        }
        return (!empty($this->errorMessages));
    }

    /**
     * Add a custom error message to return in the event of failed validation
     *
     * @param  string $message
     * @return Form
     */
    public function addErrorMessage($message)
    {
        $this->errorMessages[] = (string) $message;
        return $this;
    }

    /**
     * Add multiple custom error messages to return in the event of failed validation
     *
     * @param  array $messages
     * @return Form
     */
    public function addErrorMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->addErrorMessage($message);
        }
        return $this;
    }

    /**
     * Same as addErrorMessages(), but clears custom error message stack first
     *
     * @param  array $messages
     * @return Form
     */
    public function setErrorMessages(array $messages)
    {
        $this->clearErrorMessages();
        return $this->addErrorMessages($messages);
    }

    /**
     * Retrieve custom error messages
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Clear custom error messages stack
     *
     * @return Form
     */
    public function clearErrorMessages()
    {
        $this->errorMessages = array();
        return $this;
    }

    /**
     * Defined by ResourceInterface; returns the string identifier of the Resource
     *
     * Method signature modified to get resource ids for form elements as it is
     * not feasible to create a custom class for each form element just to implement
     * ResourceInterface. Also, a form element is tied to the form, hence better to put here.
     *
     * @param  string $elementOrFieldset Optional name of form element or fieldset to get resource id for
     * @return string Defaults to class name in lowercase
     */
    public function getResourceId($elementOrFieldset = '')
    {
        if (null === $this->resourceId) {
            $this->resourceId = strtolower(str_replace("\\", '_', get_class($this)));
        }

        $resourceId = (empty($this->parentResourceId) ? '' : $this->parentResourceId . '.')
                    . $this->resourceId;

        if (empty($elementOrFieldset) || !$this->has($elementOrFieldset)) {
            return $resourceId;
        }

        return $resourceId . '.' . strtolower($elementOrFieldset);
    }

    /**
     * Set parent resource id of form
     *
     * @param  string $parentResourceId Resource id of form's parent
     * @return AbstractForm For fluent interface
     */
    public function setParentResourceId($parentResourceId)
    {
        $this->parentResourceId = strtolower($parentResourceId);
        return $this;
    }
}
