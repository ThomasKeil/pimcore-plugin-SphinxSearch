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

  /**
   * @var bool
   */
  public static $reindexing_enabled = true;

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

  public function postUpdateObject(Object_Abstract $object) {
    self::reindex_objects($object);
  }

  public function postAddObject(Object_Abstract $object) {
    self::reindex_objects($object);
  }

  public function preDeleteObject(Object_Abstract $object){
    self::reindex_objects($object);
  }

  public static function reindex_objects($object) {
    $config = SphinxSearch_Config::getInstance();
    $plugin_config = $config->getConfig();
    switch ($plugin_config->indexer->onchange) {
      case "immediately":

        $indexes = array();
        foreach ($config->getClasses()->children() as $class) {
          /**
           * @var $class SimpleXMLElement
           */
          if ($object instanceof Object_Abstract) {
            $class_name = $class->getName();
          } else {
            $class_name = $object;
          }
          if (strtolower("Object_".$class_name) == strtolower(get_class($object))) {
            $object_class = Object_Class::getByName($class_name);
            // Do we have localized fields?
            if($object_class->getFieldDefinition("localizedfields")) {
              $pimcore_languages = Pimcore_Tool::getValidLanguages();
              foreach ($pimcore_languages as $lang) {
                $source_class_name = $class_name."_".$lang;
                $indexes[] = "idx_".$source_class_name;
              }
            } else {
              $indexes[] = "idx_".$class_name;
            }
          }
        }
        if (sizeof($indexes) > 0) {
          $indexes = implode(" ", $indexes);
          self::runIndexer($indexes);
        }
        break;

      case "reschedule":
        $plugin_config->indexer->lastrun = 0;
        $writer = new Zend_Config_Writer_Xml(array(
          "config" => $plugin_config,
          "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
        ));
        $writer->write();
        break;

      default:
        // Do nothing
        break;
    }
  }

  public function postAddDocument(Document $document){
    $this->reindex_documents($document);
  }

  public function postUpdateDocument(Document $document){
    $this->reindex_documents($document);
  }

  public function preDeleteDocument(Document $document){
    $this->reindex_documents($document);
  }


  private function reindex_documents(Document $document) {
    if (!$document instanceof Document_Page) return;

    $config = SphinxSearch_Config::getInstance();
    $plugin_config = $config->getConfig();
    switch ($plugin_config->indexer->onchange) {
      case "immediately":
        $documents_config = $config->getDocumentsAsArray();

        $languages = array("all");
        if ($this->config->documents->use_i18n == "true") {
          $languages = Pimcore_Tool::getValidLanguages();
        }

        $controller = $document->getController();
        $action = $document->getAction();
        $template = $document->getTemplate();

        $config_name = $controller."_".$action;
        if ($template != "") $config_name."_".$template;

        $indexes = array();
        foreach ($languages as $lang) {
          foreach ($documents_config as $document_name => $document_properties) {
            if ($config_name == $document_name) {
              $indexes[] = "idx_document_".$document_name."_".$lang;
            }
          }
        }
        if (sizeof($indexes) > 0) {
          $indexes = implode(" ", $indexes);
          $this->runIndexer($indexes);
        }
        break;

      case "reschedule":
        $plugin_config->indexer->lastrun = 0;
        $writer = new Zend_Config_Writer_Xml(array(
          "config" => $plugin_config,
          "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
        ));
        $writer->write();
        break;

      default:
        // Do nothing
        break;
    }


  }

  public static function runIndexer($index_name = null) {
    if (is_null($index_name)) {
      $index_name = "--all";
    }
    $lockfile = SPHINX_VAR.DIRECTORY_SEPARATOR."lock.txt";
    $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications
    $output = array();

    $indexer = "/usr/bin/indexer";
    if (isset($config->path->indexer) && $config->path->indexer != "") {
      $indexer = $config->path->indexer;
    }

    if (!(is_file($indexer) && is_executable($indexer))) {
      $message = "SphinxSearch Indexer could not be executed at ".$indexer;
      logger::err($message);
      $output[] = $message;
      $return_var = 1;
    } else {

      $fp = @fopen($lockfile, "a+");

      if ($fp === false) {
        $message = "SphinxSearch Indexer could open lockfile ".$lockfile;
        logger::err($message);
        $output[] = $message;
        $return_var = 1;
      } else {
        if (flock($fp, LOCK_EX|LOCK_NB)) {
          logger::debug("SphinxSearch Indexer locked with ".$lockfile);
          ftruncate($fp, 0);
          fwrite($fp, getmypid());
          fflush($fp);

          exec("$indexer --config ".SPHINX_VAR.DIRECTORY_SEPARATOR."sphinx.conf ".$index_name." --rotate ", $output, $return_var);

          if ($return_var == 0) {
            $config->indexer->lastrun = time();
            $writer = new Zend_Config_Writer_Xml(array(
              "config" => $config,
              "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
            ));
            $writer->write();
          }

          flock($fp, LOCK_UN);    // release the lock
          logger::debug("SphinxSearch Indexer unlocked".$lockfile);
          $return_var = 0;
        } else {
          $message = "SphinxSearch Indexer is not executed: locked with ".$lockfile;
          logger::err($message);
          $output[] = $message;
          $return_var = 1;
        }
        fclose($fp);
      }
    }
    return array("output" => implode("\n",$output), "return_var" => $return_var);
  }
}

// --config /var/www/www.kontron-development.com/htdocs/website/var/plugins/SphinxSearch/sphinx.conf --all --rotate