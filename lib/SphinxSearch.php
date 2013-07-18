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


class SphinxSearch {

  /**
   * @var $classes SphinxSearch_Classes
   */
  private $classes;


  public static function queryObjects($query, $class_name, $params = array()) {
    $search_result = self::searchObjects($query, $class_name, $params);
    $objectString = "Object_".ucfirst($class_name);

    $entries = array();
    if ($search_result["total_found"] > 0) {
      foreach ($search_result["matches"] as $id => $meta) {
        $entries[] = array("result" => $objectString::getById($id), "id" => $id, "meta" => $meta, "type" => "object", "class" => $class_name);
      }
    }
    return $entries;
  }

  public static function countObjects($query, $class_name, $params = array()) {
    $search_result = self::searchObjects($query, $class_name, $params);
    return $search_result["total_found"];
  }

  private function searchObjects($query, $class_name, $params = array()) {
    if (trim($query) == "") return array();

    $sphinx_config = SphinxSearch_Config::getInstance();
    $config = $sphinx_config->getConfig();

    $SphinxClient = new SphinxClient();

    $SphinxClient->SetMatchMode(SPH_MATCH_EXTENDED2);

    if (array_key_exists("language", $params)) {
      $language = $params["language"];
    } else {
      $locale = Zend_Registry::get("Zend_Locale");
      $language = $locale->getLanguage();
    }

    if (array_key_exists("orderKey", $params)) {
      $order = "ASC";
      if (array_key_exists("order", $params)) {
        $order = $params["order"] == "DESC" ? "DESC" : "ASC";
      }
      $SphinxClient->SetSortMode(SPH_SORT_EXPR, $params["orderKey"]. " ".$order);
    }

    $max_results = intval($config->maxresults);
    if (array_key_exists("max_results", $params)) {
      $max_results = intval($params["max_results"]);
      if ($max_results < 1) $max_results = 20; // Sphinx default actually
    }

    $offset = 0;
    if (array_key_exists("offset", $params)) {
      $offset = intval($params["offset"]);
    }

    $SphinxClient->setLimits($offset, $max_results);

    $class_config = $sphinx_config->getClassesAsArray(); // The configuration
    $field_weights = array();
    foreach ($class_config[strtolower($class_name)] as $field_name => $field_config) {
      if (array_key_exists("weight", $field_config)) {
        $field_weights[$field_name] = $field_config["weight"];
      }
    }
    if (sizeof($field_weights) > 0) $SphinxClient->setFieldWeights($field_weights);

    $index = "idx_".strtolower($class_name)."_".$language;

    $search_result = $SphinxClient->Query($query, $index);
    if ($search_result === false ) {
      throw new Exception($SphinxClient->GetLastError());
    }

    return $search_result;
  }

  public static function queryDocument($query, $params = array()) {
    if (trim($query) == "") return array();

    $sphinx_config = SphinxSearch_Config::getInstance();
    $plugin_config = $sphinx_config->getConfig();
    $documents_config = $sphinx_config->getDocumentsAsArray();

    $SphinxClient = new SphinxClient();

    $entries = array();

    $language = "all";
    if ($plugin_config->documents->use_i18n == "true") {
      if (array_key_exists("language", $params)) {
        $language = $params["language"];
      } else {
        $locale = Zend_Registry::get("Zend_Locale");
        $language = $locale->getLanguage();
      }
    }

    /*
       foreach ($documents_config as $document_name => $document_properties) {
          $index_name = "idx_document_".$document_name."_".$language;

          $field_weights = array();
          foreach ($document_properties["elements"] as $field_name => $field_config) {
            if (array_key_exists("weight", $field_config) && intval($field_config["weight"]) > 0) {
              $field_weights[$field_name] = intval($field_config["weight"]);
            }
          }
          var_dump($field_weights);
          if (sizeof($field_weights) > 0) $SphinxClient->setFieldWeights($field_weights);

          $search_result = $SphinxClient->Query($query, $index_name);
          if ($search_result === false ) {
            throw new Exception($SphinxClient->GetLastError());
          }

          if ($search_result["total_found"] > 0) {
            foreach ($search_result["matches"] as $id => $meta) {
              $entries[] = Document::getById($id);
            }
          }
        }*/

    $field_weights = array();
    $indexes = array();
    foreach ($documents_config as $document_name => $document_properties) {
      $indexes[] = "idx_document_".$document_name."_".$language;
      foreach ($document_properties["elements"] as $field_name => $field_config) {
        if (array_key_exists("weight", $field_config) && intval($field_config["weight"]) > 0) {
          $field_weights[$field_name] = intval($field_config["weight"]);
        }
      }
    }
    if (sizeof($field_weights) > 0) $SphinxClient->setFieldWeights($field_weights);

    $search_result = $SphinxClient->Query($query, implode(", ", $indexes));
    if ($search_result === false ) {
      throw new Exception($SphinxClient->GetLastError());
    }

    if ($search_result["total_found"] > 0) {
      foreach ($search_result["matches"] as $id => $meta) {
        $entries[] = array("result" => Document::getById($id), "id" => $id, "meta" => $meta, "type" => "document");
      }
    }


    return $entries;
  }

}