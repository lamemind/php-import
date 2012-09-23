<?php
/**
 * php-import (c) by Enrico Ballardini (lamemind@gmail.com)
 * 
 * php-import is licensed under a
 * Creative Commons Attribution-NonCommercial 3.0 Unported License.
 * 
 * You should have received a copy of the license along with this
 * work.  If not, see <http://creativecommons.org/licenses/by-nc/3.0/>.
 */

/**
 * This class is used to analize a specified directory and build an object structure to let
 * you easily include php files in your script.
 */
class ImportGenerator
{
	const FUNCTION_DEFAULTNAME = "import";
	const DESTFILE_DEFAULTNAME = "import.inc.php";
	
	const IMPORTCLASS_DEFAULT_PREFIX = "_Ipt";
	const IMPORTCLASS_MAIN_POSTFIX = "_Root";
	
	const FUNCTIONNAME_PATTERN = "#^[a-z_][a-z0-9_]*?$#i";
	const FILENAME_PATTERN = "#^[a-z0-9_-][a-z0-9_\.-]*?[a-z0-9_-]*?$#i";
	
	const FLAG_DIR = 0;
	const FLAG_FILE = 1;
	
	
	/**
	 * Configuration property
	 * @var string
	 */
	private $target_functionName = self::FUNCTION_DEFAULTNAME;
	/**
	 * Configuration property
	 * @var string
	 */
	private $target_destFilename = self::DESTFILE_DEFAULTNAME;
	/**
	 * Configuration property
	 * @var string
	 */
	private $target_destFilepath;

	/**
	 * Configuration property
	 * @var string
	 */
	private $_rootDir;

	/**
	 * Configuration property
	 * @var array
	 */
	private $mapping_pt_excludes = array ();
	/**
	 * Configuration property
	 * @var array
	 */
	private $mapping_pt_includes = array ();

	/**
	 * Configuration property
	 * @var string
	 */
	private $namespace_ClassnamePrefix = self::IMPORTCLASS_DEFAULT_PREFIX;
	
	/**
	 * Configuration property
	 * @var string
	 */
	private $namedup_filePrefix;
	/**
	 * Configuration property
	 * @var string
	 */
	private $namedup_filePostfix;
	/**
	 * Configuration property
	 * @var string
	 */
	private $namedup_dirPrefix;
	/**
	 * Configuration property
	 * @var string
	 */
	private $namedup_dirPostfix;
	
	/**
	 * Configuration property
	 * @var string
	 */
	private $autoload_classFileExt;
	

	/**
	 * Processing property
	 * @var array
	 */
	private $_hier = array ();
	/**
	 * Processing property
	 * @var string
	 */
	private $_rootClass;
	/**
	 * Processing property
	 * @var string
	 */
	private $_buffer = "";
	/**
	 * Processing property
	 * @var string
	 */
	private $_error = "";
	
	
	
	public function __construct ()
	{
		ini_set ('display_errors', '1');
		error_reporting (PHP_INT_MAX);
	}
	public function loadConfigFile ()
	{
		$configPath = dirname (__FILE__) . "/../config.php";
		$configPath = realpath ($configPath);
		
		if (!is_file ($configPath))
			die ("You must create a config.php file, starting from the provided sample `config.sample.php`\n\n");
		
		require $configPath;
		if (!isset ($config) && !is_array ($config))
			die ("Config File has been damaged - there must be a \$config var and it must be an array");
		

		
		if (!$this->Target_setFunctionName (isset ($config['MainFunction']) && strlen ($config['MainFunction']) > 0 ?
				$config['MainFunction'] : self::FUNCTION_DEFAULTNAME))
			return false;
		
		if (!$this->Target_setDestFilename (isset ($config['DestFileName']) && strlen ($config['DestFileName']) > 0 ?
				$config['DestFileName'] : self::DESTFILE_DEFAULTNAME))
			return false;
		
		if (!$this->Main_setRootDir ($config['RootDir']))
			return false;

		if (is_array ($config['Mapping']['ExcludePatterns']))
			foreach ($config['Mapping']['ExcludePatterns'] as $pattern)
			{
				if (!$this->Mapping_addExcludePattern ($pattern))
					return false;
			}
		if (is_array ($config['Mapping']['IncludePatterns']))
			foreach ($config['Mapping']['IncludePatterns'] as $pattern)
			{
				if (!$this->Mapping_addIncludePattern ($pattern))
					return false;
			}
		
		if ($config['Namespace']['ClassnamePrefix'] && !$this->Namespace_setClassPrefix ($config['Namespace']['ClassnamePrefix']))
			return false;
		
		
		if (!$this->NameDup_setupFileObject (
				$config['NameDuplication']['FileObject']['prefix'],
				$config['NameDuplication']['FileObject']['postfix']))
			return false;
		if (!$this->NameDup_setupDirObject (
				$config['NameDuplication']['DirObject']['prefix'],
				$config['NameDuplication']['DirObject']['postfix']))
			return false;
		
		
		if (!$this->Autoload_setClassfileExtension ($config['ClassAutoload']['ClassFileExtension']))
			return false;
		
		return true;
	}
	
	
	
	/**
	 * @param string $funcName
	 * @return boolean
	 */
	public function Target_setFunctionName ($funcName)
	{
		if (preg_match (self::FUNCTIONNAME_PATTERN, $funcName) == 0)
			return $this->err ("MainFunction name `$funcName` is invalid");
		
		$this->target_functionName = $funcName;
		return true;
	}
	/**
	 * @param string $destFilename
	 * @return boolean
	 */
	public function Target_setDestFilename ($destFilename)
	{
		if (preg_match (self::FILENAME_PATTERN, $destFilename) == 0)
			return $this->err ("DestFileName `$destFilename` is invalid");
		
		$this->target_destFilename = $destFilename;
		return true;
	}
	/**
	 * @param string $destFilepath
	 * @return boolean
	 */
	public function Target_setDestFilepath ($destFilepath)
	{
		if (!is_dir ($destFilepath) || !is_writable ($destFilepath))
			return $this->err ("DestFilePath `$destFilepath` is invalid");

		$this->target_destFilepath = $destFilepath;
		return true;
	}
	/**
	 * @return string
	 */
	public function getDestFilepath ()
	{
		return $this->target_destFilepath . $this->target_destFilename;
	}
	
	
	/**
	 * @param string $rootDir
	 * @return bool
	 */
	public function Main_setRootDir ($rootDir)
	{
		if (!is_dir ($rootDir) || !is_readable ($rootDir))
			return $this->err ("RootDir `$rootDir` is invalid");
		
		$rootDir = realpath ($rootDir);
		if (substr ($rootDir, -1) !== "/")
			$rootDir .= "/";
	
		$this->_rootDir = $rootDir;
		if ($this->target_destFilepath === null)
			$this->target_destFilepath = $rootDir;
			
		return true;
	}
	
	
	/**
	 * @param string $exclude
	 * @return boolean
	 */
	public function Mapping_addExcludePattern ($exclude)
	{
		$this->mapping_pt_excludes[] = $exclude;
		return true;
	}
	/**
	 * @param string $include
	 * @return boolean
	 */
	public function Mapping_addIncludePattern ($include)
	{
		$this->mapping_pt_includes[] = $include;
		return true;
	}
	
	
	/**
	 * @param string $prefix
	 * @return boolean
	 */
	public function Namespace_setClassPrefix ($prefix)
	{
		if (preg_match (self::FUNCTIONNAME_PATTERN, $prefix) == 0)
			return $this->err ("ClassPrefix `$prefix` is invalid");
		
		$this->namespace_ClassnamePrefix = $prefix;
		return true;
	}

	
	
	/**
	 * @param string $prefix Prefix to be applied to file elements
	 * @param string $postfix Postfix to be applied to file elements
	 * @return bool
	 */
	public function NameDup_setupFileObject ($prefix, $postFix)
	{
		if (strlen ($prefix) > 0 && preg_match (self::FUNCTIONNAME_PATTERN, $prefix) == 0)
			return $this->err ("FileObject prefix `$prefix` is invalid");
		if (strlen ($postFix) > 0 && preg_match (self::FUNCTIONNAME_PATTERN, $postFix) == 0)
			return $this->err ("FileObject postfix `$postFix` is invalid");
		
		$this->namedup_filePrefix = $prefix;
		$this->namedup_filePostfix = $postFix;
		return true;
	}
	/**
	 * @param string $prefix Prefix to be applied to directory elements
	 * @param string $postfix Postfix to be applied to directory elements
	 * @return bool
	 */
	public function NameDup_setupDirObject ($prefix = self::DIROBJ_DEFAULT_PREFIX, $postFix = self::DIROBJ_DEFAULT_POSTFIX)
	{
		if (strlen ($prefix) > 0 && preg_match (self::FUNCTIONNAME_PATTERN, $prefix) == 0)
			return $this->err ("DirObject prefix `$prefix` is invalid");
		if (strlen ($postFix) > 0 && preg_match (self::FUNCTIONNAME_PATTERN, $postFix) == 0)
			return $this->err ("DirObject prefix `$prefix` is invalid");
		
		$this->namedup_dirPrefix = $prefix;
		$this->namedup_dirPostfix = $postFix;
		return true;
	}

	
	
	/**
	 * @param string $fileExt
	 * @return boolean
	 */
	public function Autoload_setClassfileExtension ($fileExt)
	{
		$this->autoload_classFileExt = $fileExt;
		return true;
	}
	
	
	
	public function build ()
	{
		if ($this->_error)
			return false;
		
		$destFilePath = $this->target_destFilepath . $this->target_destFilename;
		if (is_file ($destFilePath))
			unlink ($destFilePath);
		
		$this->_rootClass = $this->namespace_ClassnamePrefix . self::IMPORTCLASS_MAIN_POSTFIX;
		$this->initBuffer ();
		
		$dirList = $this->scanDir ($this->_rootDir);
		if ($dirList === null)
			return false;
		foreach ($dirList as $item)
		{
			$path = $this->_rootDir . $item;
			
			if (is_dir ($path))
				$result = $this->recursiveBuild (0, $item, $this->namespace_ClassnamePrefix, $item, $this->_hier);
			else if (is_file ($path))
				$result = $this->addElement ($item, $item, $this->_hier);
			
			if (!$result)
				return false;
		}
		
		$this->makeClass ($this->_rootClass, "", $this->_hier);
		$this->writeFile ();
		return true;
	}
	private function recursiveBuild ($level, $dirPath, $classNamePrefix, $itemname, &$arrayContainer)
	{
		$cleanitem = $this->cleanName ($itemname);
		if (strlen ($cleanitem) == 0)
			return $this->err ("DirPath `$dirPath`, Item `$itemname` is invalid");
		$cleanitem = $this->namedup_dirPrefix . $cleanitem . $this->namedup_dirPostfix;
		$lower = strtolower ($cleanitem);
		
		$className = $classNamePrefix . $this->nextHash ($level);
		
		if (isset ($arrayContainer[ $lower ]))
			return $this->err ("NameDuplication: dirPath `$dirPath`, Item `$itemname` cleaned in `$lower` -> already exists - check your config.php file");
		$nestedArrayContainer = array ();
		$arrayContainer[ $lower ] = array (self::FLAG_DIR, $className, $cleanitem, $dirPath, &$nestedArrayContainer);
		
		
		$dirList = $this->scanDir ($this->_rootDir . $dirPath);
		if ($dirList === null)
			return $this->err ("Failed to Scandir `$dirPath`");
		foreach ($dirList as $item)
		{
			$subpath = $dirPath . "/" . $item;
			$fullpath = $this->_rootDir . $subpath;
			
			if (is_dir ($fullpath))
				$result = $this->recursiveBuild ($level + 1, $subpath, $className, $item, $nestedArrayContainer);
			else if (is_file ($fullpath))
				$result = $this->addElement ($subpath, $item, $nestedArrayContainer);
			
			if (!$result)
				return false;
		}
		
		$this->makeClass ($className, $dirPath . "/", $nestedArrayContainer);
		
		return true;
	}
	private function addElement ($filePath, $itemname, &$arrayContainer)
	{
		if (!$this->canAddElement ($filePath))
			return true;

		$cleanitem = $itemname;
		if (substr ($cleanitem, -10) == ".class.php")
			$cleanitem = substr ($cleanitem, 0, -10);
		else if (substr ($cleanitem, -6) == ".class")
			$cleanitem = substr ($cleanitem, 0, -6);
		else if (substr ($cleanitem, -8) == ".inc.php")
			$cleanitem = substr ($cleanitem, 0, -8);
		else if (substr ($cleanitem, -4) == ".inc")
			$cleanitem = substr ($cleanitem, 0, -4);
		else if (substr ($cleanitem, -4) == ".php")
			$cleanitem = substr ($cleanitem, 0, -4);
		
		$cleanitem = $this->cleanName ($cleanitem);
		if (strlen ($cleanitem) == 0)
			return $this->err ("DirPath `$filePath`, Item `$itemname` is invalid");
		$cleanitem = $this->namedup_filePrefix . $cleanitem . $this->namedup_filePostfix;
		$lower = strtolower ($cleanitem);
		
		if (isset ($arrayContainer[ $lower ]))
			return $this->err ("NameDuplication: dirPath `$filePath`, Item `$itemname` cleaned in `$lower` -> already exists - check your config.php file");
		$arrayContainer[ $lower ] = array (self::FLAG_FILE, $cleanitem, $itemname);
		
		return true;
	}
	
	
	
	private function initBuffer ()
	{
		$this->_buffer = "";
		
		$templateFile = file_get_contents (dirname (__FILE__) . "/TemplateFile.php");
		$templateFile = str_replace ("{{ROOT_DIR}}", $this->_rootDir, $templateFile);
		$templateFile = str_replace ("___IMPORT_FUNC_NAME___", $this->target_functionName, $templateFile);
		$templateFile = str_replace ("___IMPORT_ROOT_CLASSNAME___", $this->_rootClass, $templateFile);
		$templateFile = str_replace ("___IMPORT_CLASS_PREFIX___", $this->namespace_ClassnamePrefix, $templateFile);
		$templateFile = str_replace ("___IMPORT_CLASS_PREFIX___", $this->namespace_ClassnamePrefix, $templateFile);
		$templateFile = str_replace ("___CLASS_FILENAME_EXTENSION___", $this->autoload_classFileExt, $templateFile);
		
		if (sizeof ($this->mapping_pt_excludes) > 0)
		{
			$pattern = "array (\"" . implode ("\", \"", $this->mapping_pt_excludes) . "\")";
			$toggleStart = $toggleEnd = "";
		}
		else
		{
			$pattern = "null";
			$toggleStart = "/*";
			$toggleEnd = "*/";
		}
		$templateFile = str_replace ("___EXCLUSION_PATTERNS___", $pattern, $templateFile);
		$templateFile = str_replace ("___EXCLUSION_COMMENT_TOGGLE_START___", $toggleStart, $templateFile);
		$templateFile = str_replace ("___EXCLUSION_COMMENT_TOGGLE_END___", $toggleEnd, $templateFile);

		
		if (sizeof ($this->mapping_pt_includes) > 0)
		{
			$pattern = "array (\"" . implode ("\", \"", $this->mapping_pt_includes) . "\")";
			$toggleStart = $toggleEnd = "";
		}
		else
		{
			$pattern = "null";
			$toggleStart = "/*";
			$toggleEnd = "*/";
		}
		$templateFile = str_replace ("___INCLUSION_PATTERNS___", $pattern, $templateFile);
		$templateFile = str_replace ("___INCLUSION_COMMENT_TOGGLE_START___", $toggleStart, $templateFile);
		$templateFile = str_replace ("___INCLUSION_COMMENT_TOGGLE_END___", $toggleEnd, $templateFile);

		$templateFile = str_replace ("___FILE_PREFIX___", strlen ($this->namedup_filePrefix), $templateFile);
		$templateFile = str_replace ("___FILE_POSTFIX___", strlen ($this->namedup_filePostfix), $templateFile);
		
		
		$this->_buffer .= $templateFile;
		$this->_buffer .= "\n\n";
	}
	private function nextHash ($level)
	{
		$hash = &$this->classnameHashes[ $level ];
		if ($hash === null)
			$hash = 0;
		
		if ($hash < 26)
			$chr = chr (65 + $hash);
		else
			$chr = chr (64 + floor ($hash / 26)) . chr (65 + ($hash % 26));
		$hash++;
		
		return $chr;
	}
	private function makeClass ($classname, $dirPath, &$content)
	{
		$classComment = "/**\n";
		$classComment .= " * Autocompile Class for path \"/$dirPath\"\n * \n";
		$subClasses = array ();
		foreach ($content as $item)
		{
			$classComment .= " * @property ";
			if ($item[0] == self::FLAG_FILE)
				$classComment .= "PhpFile \$" . $item[1];
			else
			{
				$classComment .= $item[1] . " \$" . $item[2];
				$subClasses[] = "\"" . strtolower ($item[2]) . "\" => \"" . $item[1] . "\"";
			}
			$classComment .= "\n";
		}
		$classComment .= "*/\n";
		
		
		// $extendsClassname = "";
		// if ($dirPath === "")
			// $extendsClassname = $this->main_importClassPrefix . "_RootElement";
		// else
			$extendsClassname = $this->namespace_ClassnamePrefix . "_AbstractElement";
		
		$classContent = "class $classname extends $extendsClassname\n{\n";
		$classContent .= "\tprotected \$dirPath = \"" . $dirPath . "\";\n";
		$classContent .= "\tprotected \$classes = array (" . implode (", ", $subClasses) . ");\n";
		$classContent .= "\tprotected \$elements = array ();\n";
		
		// $classContent .= "\n\n\n";
		// $magicMethodContent = file_get_contents ($this->_tplDir . "magicMethod.php");
		// $classContent .= $magicMethodContent;
		$classContent .= "}\n\n\n";
		
		$this->_buffer .= $classComment . $classContent;
	}
	private function writeFile ()
	{
		$destFilePath = $this->target_destFilepath . $this->target_destFilename;
		file_put_contents ($destFilePath, $this->_buffer);
		chmod ($destFilePath, 0666);
	}
	
	
	
	private function canAddElement ($name)
	{
		foreach ($this->mapping_pt_excludes as $pattern)
			if (preg_match ($pattern, $name) > 0)
				return false;
		
		if (sizeof ($this->mapping_pt_includes) > 0)
		{
			foreach ($this->mapping_pt_includes as $pattern)
				if (preg_match ($pattern, $name) > 0)
					return true;
			
			return false;
		}
		else
			return true;
	}
	private function cleanName ($name)
	{
		if (preg_match ("#[0-9]#", $name[0]) > 0)
			$name = "_" . $name;
		$name = str_replace ("-", "_", $name);
		$name = preg_replace ("#[^a-z0-9_]#i", "", $name);
		
		return $name;
	}
	/**
	 * Ritorna i nomi dei file e delle directory contenute nel percorso dato.
	 * Ritorna solo i nomi e non percorsi completi.
	 * Non è ricorsivo!
	 * 
	 * @param string $dirPath
	 * @return array
	 */
	private function scanDir ($dirPath)
	{
		if (!is_dir ($dirPath))
			return null;
		
		$fileList = scandir ($dirPath);
		// array_splice ($fileList, 0, 2);
		foreach ($fileList as $k => $item)
			if ($item == "." || $item == "..")
				unset ($fileList[ $k ]);

		return $fileList;
	}
	
	
	
	/**
	 * @param string $errmsg
	 * @return false
	 */
	private function err ($errmsg)
	{
		$this->_error = $errmsg;
		return false;
	}
	/**
	 * Returns the last error generated during the process
	 * @return string the last error generated
	 */
	public function getError ()
	{
		return $this->_error;
	}
}


