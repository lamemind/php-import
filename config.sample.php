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

// This is a sample config file, used to help you create your own config file.
// Your config file must be named `config.php` and placed beside the `make-import.php` file
$config = array (
	"TargetFile" => array (
		// The main function name, the one you call to access your libraries
		// Default value at ImportGenerator::FUNCTION_DEFAULTNAME
		// ### not required, can be empty
		"MainFunction" => "import",
		
		// The destination file, the one you require to declare the main function
			// Default value as of ImportGenerator::DESTFILE_DEFAULTNAME
			// ### not required, can be empty
			"DestFileName" => "import.inc.php",
			
			// The file path where the destination file is created defaults to your libraries root dir.
			// You can define a different destination directory here
			// ### not required, can be empty
			"DestFilePath" => "",
	),
	

	// Root directory where your libraries reside
	// No default value
	// ### required, must be filled
	"RootDir" => "/abosolute/path/to/your/libraries/",
	
	
	"Mapping" => array (
		// Reg-ex patterns applyied to files (full path and name).
		// If a pattern matches, the file/directory is excluded from the import-file build process.
		// ### not required (the array can be emptied)
		"ExcludePatterns" => array (
			"#DISABLED#", // sample pattern
			"#\.txt$#" // sample pattern
		),
		
		// Reg-ex patterns applyied to files (full path and name).
		// If this array is empty, any file is included by default (unless it is explicitily excluded).
		// If this array is populated, any file is excluded unless at least a pattern matches.
		// ### not required (the array can be emptied)
		"IncludePatterns" => array (
			"#\.php$#" // sample pattern
		),
	),
	
	
	// This script doesn't use namespaces.
	"Namespace" => array (
		// To be sure the script will create unique class names, a prefix is applyed.
		// Default value as of ImportGenerator::IMPORTCLASS_DEFAULT_PREFIX
		// ### required, must be at least one character
		"ClassnamePrefix" => "_Ipt"
	),
	
	
	// FileObject and DirObject options allow you to avoid name duplication.
	// In some circumstances those options are essentials, sometimes are just useless
	// If you got something like that
	// ---+  /directory/
	//    |  /directory/item.php
	//    |  /directory/item/
	//    
	// then you will get a `NameDuplication` error: the `item` element is created twice inside the import data-structure.
	// In this situation it's necessary to make the `item` elements different in name.
	// ### all the properties are required, prefixes and postfixes can be empty strings
	"NameDuplication" => array (
		"FileObject" => array (
			"prefix" => "",
			"postfix" => ""
		),
		"DirObject" => array (
			"prefix" => "",
			"postfix" => ""
		)
	),
	
	
	// Class Autoload options are used to define what files include in the script
	// when a class is called and it does not exists.
	// ### required and must be filled
	"ClassAutoload" => array (
		"ClassFileExtension" => ".php"
	)
);
return $config;

