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


if (!defined("SPHINX_PLUGIN")) define("SPHINX_PLUGIN", PIMCORE_PLUGINS_PATH.DIRECTORY_SEPARATOR."SphinxSearch");
if (!defined("SPHINX_VAR"))    define("SPHINX_VAR", PIMCORE_WEBSITE_PATH . "/var/plugins/SphinxSearch");

if (!class_exists("SphinxClient")) require_once("sphinxapi.php");

class SphinxSearch_Plugin extends Pimcore_API_Plugin_Abstract implements Pimcore_API_Plugin_Interface {

  public static function needsReloadAfterInstall() {
      return true; // User muss neu geladen werden
  }

  public static function install() {
    if (!is_dir(SPHINX_VAR)) mkdir(SPHINX_VAR);
    if (!is_dir(SPHINX_VAR.DIRECTORY_SEPARATOR."index")) mkdir(SPHINX_VAR.DIRECTORY_SEPARATOR."index");

    foreach (array("classes.xml", "config.xml") as $config_file) {
      if (!file_exists(SPHINX_VAR.DIRECTORY_SEPARATOR.$config_file)) {
        copy(SPHINX_PLUGIN.DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR.$config_file, SPHINX_VAR.DIRECTORY_SEPARATOR.$config_file);
      }

    }

    if (self::isInstalled()) {
        return "SphinxSearch Plugin successfully installed.";
    } else {
        return "SphinxSearch Plugin could not be installed";
    }
  }

  public static function uninstall() {

    // TODO: Remove stuff

    if (!self::isInstalled()) {
        return "SphinxSearch Plugin successfully uninstalled.";
    } else {
        return "SphinxSearch Plugin could not be uninstalled";
    }
  }

  public static function isInstalled() {
    if (!is_dir(SPHINX_VAR)) return false;
    if (!is_file(SPHINX_VAR."/config.xml")) return false;
    return true;
  }

  public function preDispatch() {

  }

  /**
   *
   * @param string $language
   * @return string path to the translation file relative to plugin direcory
   */
  public static function getTranslationFile($language) {
    if(file_exists(PIMCORE_PLUGINS_PATH . "/SphinxSearch/texts/" . $language . ".csv")){
      return "/SphinxSearch/texts/" . $language . ".csv";
    }
    return "/SphinxSearch/texts/de.csv";
  }

  /**
   * Hook called when maintenance script is called
   */
  public function maintenance() {
    if (self::isInstalled()) {
      $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications

      if ($config->indexer->runwithmaintenance != 1) {
        logger::debug("SphinxSearch Indexer is not configured to be run with maintenance.");
        return;
      }
      $now = time();

      $last_run = $config->indexer->lastrun;

      $period = $config->indexer->period;

      if (($now - $period) < $last_run) {
        logger::debug("SphinxSearch Indexer ran at ".$last_run.", period is ".$period.", will be started in ".($now - $period + $last_run)." seconds");
        return;
      }

      logger::debug("SphinxSearch Indexer: starting");


      $output = self::runIndexer();

    } else {
      logger::debug("SphinxSearch Plugin is not installed - no maintenance to do for this plugin.");
    }
  }

  public static function runIndexer() {
    $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications

    $indexer = "/usr/bin/indexer";
    if (isset($config->path->indexer) && $config->path->indexer != "") {
      $indexer = $config->path->indexer;
    }

    if (!(is_file($indexer) && is_executable($indexer))) {
      logger::err("SphinxSearch Indexer could not be executed at ".$indexer);
      return;
    }

    $output = array();
    $return_var = 0;

    exec("$indexer --config ".SPHINX_VAR.DIRECTORY_SEPARATOR."sphinx.conf --all --rotate ", $output, $return_var);

    if ($return_var == 0) {
      $config->indexer->lastrun = time();
      $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
      ));
      $writer->write();
    }


    return array("output" => implode("\n",$output), "return_var" => $return_var);
  }
}

// --config /var/www/www.kontron-development.com/htdocs/website/var/plugins/SphinxSearch/sphinx.conf --all --rotate