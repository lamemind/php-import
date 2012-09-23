<?php
/**
 * php-import (c) by Enrico Ballardini (aka LameMind) (lamemind@gmail.com)
 * 
 * php-import is licensed under a
 * Creative Commons Attribution-NonCommercial 3.0 Unported License.
 * 
 * You should have received a copy of the license along with this
 * work.  If not, see <http://creativecommons.org/licenses/by-nc/3.0/>.
 */

/**
 * @return ___IMPORT_ROOT_CLASSNAME___
 */
function ___IMPORT_FUNC_NAME___ ()
{
	static $import;
	if ($import === null)
		$import = new ___IMPORT_ROOT_CLASSNAME___ ();
	
	return $import;
}



abstract class ___IMPORT_CLASS_PREFIX____Autoload
{
	/**
	 * @var string
	 */
	private static $classFileExtension = "___CLASS_FILENAME_EXTENSION___";
	private static $autoloadHandler = array ("___IMPORT_CLASS_PREFIX____Autoload", "__autoload");
	private static $backtraceLevel;

	
	
	/**
	 * @param callback $callback 
	 */
	public static function registerAutoload ()
	{
		// check to see if there is an existing __autoload function from another library
		if (!function_exists ('__autoload'))
		{
			if (function_exists ('spl_autoload_register'))
			{
				// we have SPL, so register the autoload function
				spl_autoload_register (self::$autoloadHandler);
				self::$backtraceLevel = 1;
			}
			else
			{
				// if there isn't, we don't need to worry about using the stack,
				// we can just register our own autoloader
				self::$backtraceLevel = 0;
				function __autoload ($className)
				{
					call_user_func (self::$autoloadHandler, $className);
				}
			}

		}
		else
		{
			// there is an existing __autoload function, we need to use a stack
			// if SPL is installed, we can use spl_autoload_register,
			// otherwise we can't do anything about it, and
			// will have to die
			if (function_exists ('spl_autoload_register'))
			{
				// we have SPL, so register both the
				// original __autoload from the external app,
				// because the original will get overwritten by the stack,
				// plus our own
				self::$backtraceLevel = 1;
				spl_autoload_register ('__autoload');
				spl_autoload_register (self::$autoloadHandler);
			}
			else
			{
				throw new RuntimeException ("__autoload function already defined and no SPL support");
			}
		}
	}

	
	
	public static function __autoload ($className)
	{
		$backtrace = debug_backtrace (false);
		$lastCall = $backtrace[ self::$backtraceLevel ];

		if (!isset ($lastCall['file']))
			throw new RuntimeException ("Autoload: `$className` not found");
		
		$filePath = $lastCall['file'];
		$dirPath = dirname ($filePath);

		$filename = $className . self::$classFileExtension;

		$requirePath = $dirPath . "/" . $filename;
		if (file_exists ($requirePath))
		{
			require_once $requirePath;
			return;
		}

		$requirePath = realpath ($dirPath . "/../" . $filename);
		if (file_exists ($requirePath))
		{
			require_once $requirePath;
			return;
		}


		throw new RuntimeException ("Autoload: `$className`, file `$filename` not found");
	}	
}



abstract class ___IMPORT_CLASS_PREFIX____AbstractElement
{
	const ROOT_DIR = "{{ROOT_DIR}}";
	
	
	
	private static $inclusionPatters = ___INCLUSION_PATTERNS___;
	private static $exclusionPatters = ___EXCLUSION_PATTERNS___;
	private static $filePrefixLength = ___FILE_PREFIX___;
	private static $filePostfixLength = ___FILE_POSTFIX___;
	
	
	
	/**
	 * Ritorna il percorso assoluto della libreria
	 * @return string
	 */
	public function fullpath ()
	{
		return self::ROOT_DIR . $this->dirPath;
	}
	/**
	 * Ritorna il percorso relativo della libreria
	 * @return string
	 */
	public function path ()
	{
		return $this->dirPath;
	}



	public function __get ($propName)
	{
		if (self::$filePostfixLength > 0)
			$propToFilename = substr ($propName, self::$filePrefixLength, - self::$filePostfixLength);
		else
			$propToFilename = substr ($propName, self::$filePrefixLength);
		
		$filePath = self::ROOT_DIR . $this->dirPath . $propToFilename;
		if (is_file ($filePath . ".php"))
		{
			return require_once $filePath . ".php";
		}


		if (!isset ($this->elements[ $propName ]))
		{
			$className = $this->classes[ strtolower ($propName) ];
			$this->elements[ $propName ] = new $className ();
		}

		return $this->elements[ $propName ];
	}
	/**
	 * @param bool $recursive
	 */
	public function load ($recursive = false)
	{
		$dirPath = self::ROOT_DIR . $this->dirPath;
		if (!is_dir ($dirPath))
			throw new RuntimeException ("Unable to load directory \"" . $dirPath . "\"");


		static $requiredDirs = array ();
		if (!$recursive)
		{
			if (isset ($requiredDirs[ $dirPath ]))
				return;
			$requiredDirs[ $dirPath ] = true;
		}


		if ($recursive)
		{
			$orgIterator = new RecursiveDirectoryIterator ($dirPath, 4096);
			$trueIterator = new RecursiveIteratorIterator ($orgIterator, RecursiveIteratorIterator::SELF_FIRST);
			$trueIterator->rewind ();
		}
		else
		{
			$orgIterator = new DirectoryIterator ($dirPath);
			$trueIterator = new IteratorIterator ($orgIterator);
		}
		foreach ($trueIterator as $fileInfo)
		{
			/* @var $fileInfo SplFileInfo */
			if (!$fileInfo->isFile ())
				continue;

			___EXCLUSION_COMMENT_TOGGLE_START___;
			$exclude = false;
			foreach (self::$exclusionPatters as $pattern)
				if (preg_match ($pattern, $fileInfo->getPathname ()) > 0)
				{
					$exclude = true;
					break;
				}
			if ($exclude)
				continue;
			___EXCLUSION_COMMENT_TOGGLE_END___;
			
			___INCLUSION_COMMENT_TOGGLE_START___;
			$exclude = true;
			foreach (self::$inclusionPatters as $pattern)
				if (preg_match ($pattern, $fileInfo->getPathname ()) > 0)
				{
					$exclude = false;
					break;
				}
			if ($exclude)
				continue;
			___INCLUSION_COMMENT_TOGGLE_END___;
			

			require_once $fileInfo->getPathname ();
		}


		$orgIterator->rewind ();
		$trueIterator->rewind ();
	}
}



___IMPORT_CLASS_PREFIX____Autoload::registerAutoload ();

