### Example for znZendFlashMessages

```php
// In controller
namespace Web\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $this->flashMessenger()->addMessage('Hello World!');
        $this->flashMessenger()->addMessage('Microtime is ' . microtime(true));
    }
}
```

```php
<!-- In view script -->
<ul>
  <?php foreach ($this->znZendFlashMessages() as $message): ?>
    <li><?php echo $message; ?></li>
  <?php endforeach; ?>
</ul>
```
_BECOMES_
```
<ul>
  <li>Hello World!</li>
  <li>Microtime is 1356862364.4435</li>
</ul>
```