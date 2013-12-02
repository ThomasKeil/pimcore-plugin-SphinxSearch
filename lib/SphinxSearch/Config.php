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


class SphinxSearch_Config {

  private $classes;

  /**
   * Singleton instance
   *
   * @var Zend_Auth
   */
  protected static $_instance = null;

  /**
   * Returns an instance of SphinxSearch_Config
   *
   * Singleton pattern implementation
   *
   * @return SphinxSearch_Config
   */
  public static function getInstance() {
    if (null === self::$_instance) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  public function __construct() {
    $this->classes = simplexml_load_file(SPHINX_VAR.DIRECTORY_SEPARATOR."classes.xml");
  }


  public function getClasses() {
    return $this->classes->classes;
  }

  public function getDocuments() {
    return $this->classes->documents;
  }

  public function getClassesAsArray() {
    $classes = array();
    foreach ($this->classes->classes->children() as $class_node) {
      $class_name = $class_node->getName();
      $classes[$class_name] = array();
      foreach ($class_node->children() as $child_node) {
        $attributes = array();
        foreach ($child_node->attributes() as $key => $value) $attributes[$key] = $value;
        $name = strval($attributes["name"]);
        $classes[$class_name][$name] = array(
          "weight" => array_key_exists("weight", $attributes) ? intval($attributes["weight"]) : 1,
          "store_attribute" => array_key_exists("field_type", $attributes)
        );
      }
    }
    return $classes;
  }

  public function getDocumentsAsArray() {
    $documents = array();
    foreach ($this->classes->documents->children() as $document_node) {
      $document_name = $document_node->getName();
      $documents[$document_name] = array();
      foreach ($document_node->attributes() as $key => $value) $documents[$document_name][$key] = $value->__toString();

      $documents[$document_name]["elements"] = array();
      foreach ($document_node->children() as $input_element) {
        $input_name = $input_element->getName();
        $documents[$document_name]["elements"][$input_name] = array();
        foreach ($input_element->attributes() as $key => $value) {
          $documents[$document_name]["elements"][$input_name][$key] = $value->__toString();
        }
      }
    }
//    var_dump($documents);
    return $documents;
  }


  public function writeXml() {
    $this->classes->asXML(SPHINX_VAR.DIRECTORY_SEPARATOR."classes.xml");
  }

  public function writeSphinxConfig() {
    $pimcore_config = Pimcore_Config::getSystemConfig();
    $params = $pimcore_config->database->params;
    $db_host = $params->host;
    $db_user = $params->username;
    $db_password = $params->password;
    $db_name = $params->dbname;
    $db_port = $params->port;
    $index_path = SPHINX_VAR.DIRECTORY_SEPARATOR."index";
    $cli_path = PIMCORE_PLUGINS_PATH.DIRECTORY_SEPARATOR."SphinxSearch".DIRECTORY_SEPARATOR."cli";

    $pid_path  = SphinxSearch_Config_Plugin::getValue("path", "pid");
    $logfile_path = SphinxSearch_Config_Plugin::getValue("path", "log");
    $querylog_path = SphinxSearch_Config_Plugin::getValue("path", "querylog");
    $port = SphinxSearch_Config_Plugin::getValue("searchd", "port");

    if (substr($pid_path,0,1) != DIRECTORY_SEPARATOR) $pid_path = PIMCORE_DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$pid_path;
    if (substr($logfile_path,0,1) != DIRECTORY_SEPARATOR) $logfile_path = PIMCORE_DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$logfile_path;
    if (substr($querylog_path,0,1) != DIRECTORY_SEPARATOR) $querylog_path = PIMCORE_DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$querylog_path;

$config = <<<EOL

indexer
{
mem_limit = 32M
}

searchd
{
port = $port
log = $logfile_path
query_log = $querylog_path
read_timeout = 5
max_children = 30
pid_file = $pid_path
max_matches = 1000
seamless_rotate = 1
preopen_indexes = 0
unlink_old = 1
}

EOL;

    $indexer = SphinxSearch_Config_Plugin::getValue("path", "phpcli")." ".$cli_path.DIRECTORY_SEPARATOR."index_documents.php";

    $documents_config = $this->getDocumentsAsArray();

    $languages = array("all");
    if (SphinxSearch_Config_Plugin::getValue("documents", "use_i18n") == "true") {
      $languages = Pimcore_Tool::getValidLanguages();
    }

    foreach ($languages as $lang) {
      foreach ($documents_config as $document_name => $document_properties) {
        $source_name = "document_".$document_name."_".$lang;
        $index_name = "idx_document_".$document_name."_".$lang;
        $document_index_path = $index_path.DIRECTORY_SEPARATOR."document_".$document_name."_".$lang;
        $config .= <<<EOL

source $source_name {
    type = xmlpipe2
    xmlpipe_command = $indexer -d $document_name -l $lang
}

index $index_name {
    source = $source_name
    path   = $document_index_path
    charset_type = utf-8
}

EOL;

      }
    }

    foreach ($this->getClasses()->children() as $class) {
      /**
       * @var $class SimpleXMLElement
       */

      $class_name = $class->getName();
      $object_class = Object_Class::getByName($class_name);



      $fields = array("oo_id", "o_creationDate", "o_modificationDate", "o_published", "o_type");

      $attributes = array("o_creationDate" => "sql_attr_timestamp", "o_modificationDate" => "sql_attr_timestamp", "o_published" => "sql_field_string", "o_type" => "sql_field_string");

      foreach ($class->children() as $field) {
        $fields[] = $field->attributes()->name;
        if ($field->attributes()->field_type) {
          $attributes[$field->attributes()->name->__toString()] = $field->attributes()->field_type;
        }
      }
      $fields = implode(",", $fields);

      $attributes_definition = "";
      foreach ($attributes as $key => $value) {
        $attributes_definition .= "        $value = $key\n"; // Yes, really $value first
      }

      // Do we have localized fields?
      if($object_class->getFieldDefinition("localizedfields")) {
        $pimcore_languages = Pimcore_Tool::getValidLanguages();
        foreach ($pimcore_languages as $lang) {
          $source_class_name = $class_name."_".$lang;
          $table = "object_localized_".$object_class->getId()."_".$lang;

          $config .= <<<EOL
source $source_class_name
{
        type                    = mysql

        sql_host                = $db_host
        sql_user                = $db_user
        sql_pass                = $db_password
        sql_db                  = $db_name
        sql_port                = $db_port  # optional, default is 3306

        sql_query               = SELECT $fields FROM $table
        sql_query_info          = SELECT oo_id FROM $table WHERE oo_id=\$id
$attributes_definition
}

index idx_$source_class_name
{
        source                  = $source_class_name
        path                    = $index_path/$source_class_name
        docinfo                 = extern
        charset_type            = utf-8

        min_word_len            = 1
        min_prefix_len          = 0
        min_infix_len           = 1
}

EOL;

        }
      } else {
        $table = "object_".$object_class->getId();
        $document_index_path = $index_path.DIRECTORY_SEPARATOR.$class_name;
        $config .= <<<EOL
source $class_name
{
        type                    = mysql

        sql_host                = $db_host
        sql_user                = $db_user
        sql_pass                = $db_password
        sql_db                  = $db_name
        sql_port                = $db_port  # optional, default is 3306

        sql_query               = SELECT $fields FROM $table
        sql_query_info          = SELECT oo_id FROM $table WHERE oo_id=\$id
$attributes_definition
}

index idx_$class_name
{
        source                  = $class_name
        path                    = $document_index_path
        docinfo                 = extern
        charset_type            = utf-8

        min_word_len            = 1
        min_prefix_len          = 0
        min_infix_len           = 1
}

EOL;
      }
      file_put_contents(SPHINX_VAR.DIRECTORY_SEPARATOR."sphinx.conf", $config);
    }
  }

}