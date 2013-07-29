<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 04.07.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

// http://framework.zend.com/manual/1.12/de/zend.paginator.advanced.html#zend.paginator.advanced.adapters

class SphinxSearch_DocumentList extends SphinxSearch_ListAbstract {

  public function __construct($query) {
    parent::__construct($query);

    $sphinx_config = SphinxSearch_Config::getInstance();
    $documents_config = $sphinx_config->getDocumentsAsArray();


    $field_weights = array();
    foreach ($documents_config as $document_name => $document_properties) {
      foreach ($document_properties["elements"] as $field_name => $field_config) {
        if (array_key_exists("weight", $field_config) && intval($field_config["weight"]) > 0) {
          $field_weights[$field_name] = intval($field_config["weight"]);
        }
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

    $search_result = $this->getDocumentIds();
    $sliced = array_slice($search_result, $this->offset, $this->limit, true);
    $documents = array();
    foreach ($sliced as $id) {
      $document = Document::getById($id);
      $documents[] = $document;
    }

    $this->search_result_items = $documents;
    return $this->search_result_items;

  }

  public function getTotalCount() {
    return count($this->getDocumentIds());
  }

  private function getDocumentIds() {
    if ($this->search_result_ids === null) {
      $sphinx_config = SphinxSearch_Config::getInstance();
      $documents_config = $sphinx_config->getDocumentsAsArray();

      $query = $this->query;
      if (!$this->getUnpublished()) {
        $query = $query." @o_published 1";
      }

      $language = "all";
      if ($this->plugin_config->documents->use_i18n == "true") {
        $locale = Zend_Registry::get("Zend_Locale");
        $language = $locale->getLanguage();
      }
      foreach ($documents_config as $document_name => $document_properties) {
        $indexes[] = "idx_document_".$document_name."_".$language;
      }

      $search_result = $this->SphinxClient->Query($query, implode(", ", $indexes));
      if ($search_result === false ) {
        throw new Exception($this->SphinxClient->GetLastError());
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