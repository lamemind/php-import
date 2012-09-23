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

require dirname (__FILE__) . "/src/ImportGenerator.class.php";
$import = new ImportGenerator ();
if (!$import->loadConfigFile () || !$import->build ())
	die ($import->getError () . "\n\n");

