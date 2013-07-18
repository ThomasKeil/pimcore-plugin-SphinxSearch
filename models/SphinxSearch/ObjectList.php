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

  public function current() {
    if ($this->search_result_segmented === false) {
      $this->search_result_segmented = $this->load();
    }

    $id = $this->result_ids[$this->pointer];
    $objectString = "Object_".ucfirst($this->class_name);
    $object = $objectString::getById($id);
    return $object;
  }

  protected function load() {
    $locale = Zend_Registry::get("Zend_Locale");
    $language = $locale->getLanguage();

    $index = "idx_".strtolower($this->class_name)."_".$language;
    $search_result = $this->SphinxClient->Query($this->query, $index);
    if ($search_result === false ) {
      throw new Exception($this->SphinxClient->GetLastError()."\n query:".$this->query);
    }

    return $search_result["matches"];
  }

  public function getObjects() {

    if ($this->search_result_segmented === false) {
      $this->search_result_segmented = $this->load();
    }

    $objectString = "Object_".ucfirst($this->class_name);

    $entries = array();
    foreach ($this->search_result_segmented as $id => $meta) {
      $entries[] = $objectString::getById($id);
    }
    return $entries;

  }

}