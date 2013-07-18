#!/usr/bin/php
<?php
/**
 * This source file is subject to the new BSD license that is
 * available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2013 Weblizards GbR (http://www.weblizards.de)
 * @author     Thomas Keil <thomas@weblizards.de>
 * @license    http://www.pimcore.org/license     New BSD License
 */

ini_set('memory_limit', '2048M');
set_time_limit(-1);
date_default_timezone_set("Europe/Berlin");

include_once(dirname(__FILE__)."/../../../pimcore/config/startup.php");
Pimcore::initAutoloader();
Pimcore::initConfiguration();
Pimcore::initLogger();
Pimcore::initPlugins();

/*$opts = new Zend_Console_Getopt(array(
  'language|l=s' => "language",
  'document|d=s' => "document"
));

try {
  $opts->parse();
} catch (Exception $e) {
  die ("Fehler: ".$e->getMessage());
}*/


$result = SphinxSearch_Plugin::runIndexer();
print $result["output"]."\nReturn Value: ".$result["return_var"]."\n";