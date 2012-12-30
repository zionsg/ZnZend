### Example for znZendFormTable

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
```

```php
<!-- In view script -->
<style>
  .element { padding: 5px; text-align:left; }
</style>
<?php
// Instance of login form passed in from controller as $form
echo $this->znZendFormTable()->setTdElementClass('test')->render($form);
?>
```
_BECOMES_
```
<form action="" method="post" name="loginForm" id="loginForm">
  <table class="">
    <tr class="">
      <td class=""><label><span>Userid</span></label></td>
      <td class="element">
        <input type="text" name="userid" value="">
      </td>
    </tr>
    <tr class="">
      <td class=""><label><span>Password</span></label></td>
      <td class="element">
        <input type="password" name="password" value="">
      </td>
    </tr>
    <tr class="">
      <td class=""></td>
      <td class="element">
        <input type="submit" name="submit" value="Login">
      </td>
    </tr>
  </table>
</form>
```