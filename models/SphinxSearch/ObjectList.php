<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 04.07.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

// http://framework.zend.com/manual/1.12/de/zend.paginator.advanced.html#zend.paginator.advanced.adapters

class SphinxSearch_ObjectList extends SphinxSearch_ListAbstract {

  protected $class_name;

  public function __construct($query, $class_name) {
    parent::__construct($query);
    $class_name = strtolower($class_name);
    $this->class_name = $class_name;

    $sphinx_config = SphinxSearch_Config::getInstance();
    $class_config = $sphinx_config->getClassesAsArray(); // The configuration


    $field_weights = array();
    foreach ($class_config[$this->class_name] as $field_name => $field_config) {
      if (array_key_exists("weight", $field_config)) {
        $field_weights[$field_name] = $field_config["weight"];
      }
    }
    if (sizeof($field_weights) > 0) $this->SphinxClient->setFieldWeights($field_weights);

  }

  public function current() {
    $this->load();
    return $this->search_result_items[$this->pointer];
  }

  public function load($override = false) {
    if ($this->search_result_items !== null && !$override) {
      return $this->search_result_items;
    }
    $search_result = $this->getObjectIDs();
    $sliced = array_slice($search_result, $this->offset, $this->limit, true);

    $objectString = "Object_".ucfirst($this->class_name);
    $entries = array();
    foreach ($sliced as $id) {
      $entries[] = $objectString::getById($id);
    }
    $this->search_result_items = $entries;
    return $this->search_result_items;
  }

  public function getTotalCount() {
    return count($this->getObjectIds());
  }

  private function getObjectIds() {
    if ($this->search_result_ids === null) {
      $index = "idx_".$this->class_name;
      $object_class = Object_Class::getByName($this->class_name);
      if($object_class->getFieldDefinition("localizedfields")) {
        $locale = Zend_Registry::get("Zend_Locale");
        $language = $locale->getLanguage();
        $index .= "_".$language;
      }

      $query = $this->query;
      if (!$this->getUnpublished()) {
        $query = $query." @o_published 1";
      }

      $search_result = $this->SphinxClient->Query($query, $index);
      if ($search_result === false ) {
        throw new Exception($this->SphinxClient->GetLastError()."\n query:".$this->query);
      }

      if ($search_result["total_found"] > 0) {
        $this->search_result_ids = array_keys($search_result["matches"]);
      } else {
        $this->search_result_ids = array();
      }
    }
    return $this->search_result_ids;
  }
}