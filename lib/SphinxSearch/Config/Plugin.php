<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 28.11.13
 * Time: 09:02
 */

/**
 * Class SphinxSearch_Config_Plugin
 *
 * Reads and creates the Plugin's configfile
 * located at website/var/plugins/SphinxSearch/config.xml
 */
class SphinxSearch_Config_Plugin {

  /**
   * @var array
   */
  private $config;

  private $defaults = array(
    "path" => array(
      "pid" => "website/var/plugins/SphinxSearch/searchd.pid",
      "log" => "website/var/log/sphinxsearch_searchd.log",
      "querylog" => "website/var/log/sphinxsearch_query.log",
      "indexer" => "/usr/bin/indexer",
      "phpcli" => "/usr/bin/php",
      "searchd" => "/usr/bin/searchd"
    ),
    "searchd" => array(
      "port" => "9312"
    ),
    "indexer" => array(
      "period" => 3600,
      "lastrun" => 0,
      "runwithmaintenance" => true,
      "onchange" => "reschedule"
    ),
    "documents" => array(
      "use_i18n" => false
    ),
    "results" => array(
      "maxresults" => 1000
    )
  );

  /**
   * Singleton instance
   *
   * @var Zend_Auth
   */
  protected static $_instance = null;

  /**
   * Returns an instance of SphinxSearch_Config_Plugin
   *
   * Singleton pattern implementation
   *
   * @return SphinxSearch_Config_Plugin
   */
  public static function getInstance() {
    if (null === self::$_instance) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }


  public function __construct() {
    try {
      $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml");
      $this->config = $config->toArray();
    } catch (Zend_Config_Exception $e) {
      $this->config = $this->defaults;
    }
  }

  /**
   * @return array
   */
  public function getData() {
    return $this->config;
  }


  public function setData($data) {
    $this->config = $data;
  }

  public function save() {
    $defaults = $this->defaults;
    $params = $this->getData();

    $data = $this->array_join($defaults, $params);

    $config = new Zend_Config($data, true);
    $writer = new Zend_Config_Writer_Xml(array(
      "config" => $config,
      "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
    ));
    $writer->write();
  }

  public static function setValue($section, $key, $value) {
    $config = self::getInstance();
    $data = $config->getData();
    $data[$section][$key] = $value;
    $config->setData($data);
    $config->save();
  }

  public static function getValue($section, $key) {
    $config = self::getInstance();
    $data = $config->getData();
    if (!array_key_exists($section, $data)) throw new Exception("Section $section does not exist in Config");
    if (!array_key_exists($key, $data[$section])) throw new Exception("Key $key does not exist in Section $section ");
    return $data[$section][$key];
  }

  private function array_join($original, $array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $original[$key] = $this->array_join($original[$key], $array[$key]);
      } else {
        $original[$key] = $value;
      }
    }
    return $original;
  }

}