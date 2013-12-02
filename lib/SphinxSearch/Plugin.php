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
      if (SphinxSearch_Config_Plugin::getValue("indexer", "runwithmaintenance") != 1) {
        logger::debug("SphinxSearch Indexer is not configured to be run with maintenance.");
        return;
      }
      $now = time();

      $last_run = SphinxSearch_Config_Plugin::getValue("indexer", "lastrun");
      $period = SphinxSearch_Config_Plugin::getValue("indexer", "period");

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
        SphinxSearch_Config_Plugin::setValue("indexer", "lastrun", 0);
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


  /**
   * @param bool $force
   * @return mixed
   */
  public static function startSearchd($force = false) {
    if (!self::isSearchdRunning() || $force) {
      exec("/usr/bin/searchd -c ".SPHINX_VAR.DIRECTORY_SEPARATOR."sphinx.conf", $output, $return_var);

      if ($return_var == 0) {
        return array("result" => true, "message" => "Searchd started.");
      }
      return array("result" => false, "message" => $output);
    }
    return array("result" => true, "message" => "Searchd seems already to be running (and no force was set).");
  }

  /**
   * @return mixed
   */
  public static function stopSearchd() {
    if (self::isSearchdRunning()) {

      $config = SphinxSearch_Config::getInstance();
      $plugin_config = $config->getConfig();
      $pid_file = PIMCORE_DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$plugin_config->path->pid;
      $pid = trim(file_get_contents($pid_file));

      exec("kill $pid", $output, $return_var);

      if ($return_var == 0) {
        return array("result" => true, "message" => "Searchd stopped.");
      }
      return array("result" => false, "message" => $output);
    }
    return array("result" => false, "message" => "Searchd doesn't seem to be running.");
  }


  private function reindex_documents(Document $document) {
    if (!$document instanceof Document_Page) return;

    $config = SphinxSearch_Config::getInstance();
    switch (SphinxSearch_Config_Plugin::getValue("indexer", "onchange")) {
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
        SphinxSearch_Config_Plugin::setValue("indexer", "lastrun", 0);
        break;

      default:
        // Do nothing
        break;
    }


  }

  public static function isSearchdRunning() {
    $config = SphinxSearch_Config::getInstance();
    $pid_file = PIMCORE_DOCUMENT_ROOT.DIRECTORY_SEPARATOR.SphinxSearch_Config_Plugin::getValue("path", "pid");

    if (!file_exists($pid_file)) {
      //die("PIDFILE ".$pid_file." nicht gefunden");
      return false;
    }

    $pid = trim(file_get_contents($pid_file));

    exec("ps $pid", $output, $result);
    return count($output) >= 2;
  }

  public static function runIndexer($index_name = null) {
    if (is_null($index_name)) {
      $index_name = "--all";
    }
    $lockfile = SPHINX_VAR.DIRECTORY_SEPARATOR."lock.txt";
    $output = array();

    try {
      $indexer = SphinxSearch_Config_Plugin::getValue("path", "indexer");
    } catch (Exception $e) {
      $indexer = "/usr/bin/indexer";
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

          exec("$indexer --config ".SPHINX_VAR.DIRECTORY_SEPARATOR."sphinx.conf ".$index_name.(self::isSearchdRunning() ? " --rotate " : "")." 2&>1", $output, $return_var);

          if ($return_var == 0) {
            SphinxSearch_Config_Plugin::setValue("indexer", "lastrun", time());
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