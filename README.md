php-import
==========

This package let you load (_require_) php files into your scripts with code completion,
just like the java import!


```php
// Require a single file in the script
import ()->Logging->Logger;

// Require a directory recursively in the script
import ()->HtmlTemplate->Smarty->load ($recursive = true);
```

# How to use it?
php-import is an automatic file builder. It builds an _import-file_ you have to require to get the power.
Inside the _import-file_ there is the whole directory and file structure of the libraries you provided.

That's the procedure to follow:
* Create your own `config.php` file starting from `config.sample.php`.
Fill in all the required properties, expecially your libraries root directory.
* run `php make-import.php` from the shell. I tryed to simplify things, so if anithing goes wrong, the `make-import` script will stop with an error message, explaining how to solve the issue.
* After having created a `import-file`, all you need is to require it in your script.
* Then you're done.

```shell
$ # From the shell: create your config file and run the make-import script
$ cp config.sample.php config.php
$ # edit `config.php` using your favorite text editor and fill it with all the required options
$ php make-import.php
>>> import-file `/var/www/libs/import.inc.php` built successfully
```
```php
<?php
// Then, load the built import-file
require "libs/import.inc.php";

// Then, you can
// -- require a single file in the script
import ()->Logging->Logger;

// -- require a directory recursively in the script
import ()->HtmlTemplate->Smarty->load ($recursive = true);

// -- require a directory NOT-recursively in the script
import ()->Whatever->load ($recursive = false);

// -- ask the relative path
import ()->Whatever->path ();

// -- ask the absolute path
import ()->Whatever->fullpath ();
```




# How to customize it?
It's possible to rename the `import ()` function, and other misc stuff.
Just take a look at your `config.php`, every option is there and it's explained.

