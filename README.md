php-import
==========

This package let you load php files into your scripts with code completion,
just like the java import!


```php
// Require a single file in the script
import ()->Logging->Logger;

// Require a directory recursively in the script
import ()->HtmlTemplate->Smarty->load ($recursive = true);
```


# How to use it?
1) Create your own `config.php` file starting from `config.sample.php`. Fill all the required properties, expecially your php libraries root directory.
2) run `php make-import.php` and see how it goes.
Most of the times you'll be just done.