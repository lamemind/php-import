php-import
==========

This package let you load php files into your scripts with code completition,
just like the java import!


```php
// Require a single file in the script
import ()->Logging->Logger;

// Require a directory recursively in the script
import ()->HtmlTemplate->Smarty->load ($recursive = true);
```
