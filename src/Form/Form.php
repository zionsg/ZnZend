<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
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
 *   - Params for dynamic elements can be injected via the constructor using 'params' key in options
 *     and retrieved with getParam()
 *   - init() method created for adding of elements in extending classes
 *   - CSRF element is added by default
 *   - Allows setting and getting of custom form-level error messages like in ZF1
 *   - Implements ResourceInterface allowing it to return resource id for itself
 *   - getElementResourceId() for getting resource id for an element or fieldset
 *   - Allows setting of parent resource id which will be prefixed to its own resource id
 */
class Form extends ZendForm implements ResourceInterface
{
    /**
     * Params for use with dynamic elements
     *
     * @var array
     */
    protected $params = [];

    /**
     * Custom form-level error messages
     *
     * @var array
     */
    protected $errorMessages = [];

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
     * Sample scenario for 'params' key in options: An arbitrary number of text elements
     * is to be created in the form based on some condition determined in the controller.
     * This can be used to pass in a set key with the value needed, eg. 'params' => array('textElements' => 5).
     * Allowed file extensions and size limits can be passed in to file upload elements
     * via this also.
     *
     * Extending classes should add their elements in the init() method lest they forget
     * to call the parent constructor.
     *
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->setInputFilter(new InputFilter()) // set default InputFilter
             ->add(new Element\Csrf('token'));   // add CSRF element

        // Add elements for extending classes
        $this->init();
    }

    /**
     * Initialize form (used by extending classes)
     *
     * @return  void
     */
    public function init()
    {
    }

    /**
     * Defined by \Zend\Form\ElementInterface; Set options for form. Accepted options are:
     * - params: array of key-value pairs for use with dynamic elements
     *
     * @param  array|Traversable $options
     * @return Element|ElementInterface
     * @throws Exception\InvalidArgumentException
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($options['params'])) {
            $this->setParams($options['params']);
        }

        return $this;
    }

    /**
     * Set params
     *
     * @param  array $params Key-value pairs
     * @return Form
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;
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
    public function addParams(array $params = [])
    {
        $this->params = array_merge(
            $this->params,
            $params
        );
        return $this;
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
     * Retrieve all stored params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
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
        $this->errorMessages = [];
        return $this;
    }

    /**
     * Checks if the form has errors
     *
     * Must check error messages as they could have been added via additional checks
     * after the usual form validation.
     *
     * @return bool
     */
    public function hasErrors()
    {
        if ($this->hasValidated) {
            return (! empty($this->errorMessages)) && $this->isValid;
        }
        return false;
    }

    /**
     * Defined by ResourceInterface; returns the string identifier of the Resource
     *
     * @return string Defaults to class name in lowercase
     */
    public function getResourceId()
    {
        if (null === $this->resourceId) {
            $this->resourceId = strtolower(str_replace("\\", '_', get_class($this)));
        }

        $resourceId = (empty($this->parentResourceId) ? '' : $this->parentResourceId . '.')
                    . $this->resourceId;

        return $resourceId;
    }

    /**
     * Get resource id for element
     *
     * @example getElementResourceId('name') returns znzend_form_form.name
     * @param   string $elementOrFieldset Name of form element or fieldset to get resource id for
     * @return  null|string Return null if element does not exist
     */
    public function getElementResourceId($elementOrFieldset)
    {
        if (! $this->has($elementOrFieldset)) {
            return null;
        }
        return $this->getResourceId() . '.' . strtolower($elementOrFieldset);
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
