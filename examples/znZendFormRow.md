### Example for znZendFormRow

```php
// Sample login form
use Zend\Form\Element;
use Zend\Form\Form;

class Login extends Form
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $element = new Element\Text('userid');
        $element->setLabel('Userid');
        $this->add($element);

        $element = new Element\Password('password');
        $element->setLabel('Password');
        $this->add($element);

        $element = new Element\Submit('submit');
        $element->setValue('Login');
        $this->add($element);
    }
}

```php
<!-- In view script -->
<?php
// Instance of login form passed in from controller as $form
echo $this->form()->openTag($form);
echo '<table>';
$helper = $this->znZendFormRow()->setRenderFormat('<tr><td>%label%</td><td>%element%%errors%</td></tr>');
echo $helper->render($form->get('userid'));
echo $helper->render($form->get('password'));
echo $helper->render($form->get('submit'));
echo '</table>';
?>
```
_BECOMES_
```
<form action="" method="post" name="loginForm" id="loginForm">
  <table>
    <tr><td><span>Userid</span></td><td><input type="text" name="userid" value=""></td></tr>
    <tr><td><span>Password</span></td><td><input type="password" name="password" value=""></td></tr>
    <tr><td></td><td><input type="submit" name="submit" value="Login"></td></tr>
  </table>
</form>
```