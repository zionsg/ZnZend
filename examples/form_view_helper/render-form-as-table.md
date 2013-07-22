### Rendering a form in table format

Initially, 2 form view helpers, `znZendFormRow` and `znZendFormTable`, were created for
rendering forms in table format. As of Zend Framework 2.2.0, the `formRow` helper accepts
partials, which removed the need for the custom form view helpers.

This is an example of how ZF2's own `formRow` and `formCollection` view helpers can be used to
render a form in table format. The form class used is ZnZend\Form\Form as the hasErrors() method is used.

```php
// Sample login form
namespace Web\Form;

use Zend\Form\Element;
use ZnZend\Form\Form;

class Login extends Form
{
    public function init()
    {
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
<!-- row.phtml: View partial to render each element in in <tr> row -->
<?php $errors = $this->formElementErrors()->render($element, array('class' => 'text-error')); ?>
<tr>
  <td>
    <?php if ($element->getLabel()) : ?>
      <?php echo $this->formLabel($element) ?>
    <?php endif; ?>
  </td>
  <td>
    <?php echo $this->formElement($element); ?>
    <?php if ($errors): ?>
      <small><?php echo $errors ?></small>
    <?php endif; ?>
  </td>
</tr>
```

```php
<!-- View script which renders form in table format -->
<?php if ($form->hasErrors()): ?>
  <div class="text-error text-center margin-auto">
    <?php
    foreach ($form->getErrorMessages() as $message) {
        echo $message . '<br />';
    }
    ?>
  </div><br />
<?php endif; ?>

<?php
$form->prepare();
echo $this->form()->openTag($form);
?>
  <table>
    <?php
    $rowPartial = 'web/partials/row.phtml';
    echo $this->formCollection()
              ->setElementHelper($this->formRow()->setPartial($rowPartial))
              ->render($form);
    ?>
  </table>
<?php echo $this->form()->closeTag(); ?>
```
_BECOMES_
```
<form action="" method="post" name="LoginForm" id="LoginForm">
  <table>
    <tr>
      <td><label for="userid">Userid</label></td>
      <td><input type="text" name="userid" /></td>
    </tr>

    <tr>
      <td><label for="password">Password</label></td>
      <td><input type="password" name="password" /></td>
    </tr>

    <tr>
      <td></td>
      <td><input name="submit" type="submit" id="submitButton" value="Submit"></td>
    </tr>
  </table>
</form>
```
