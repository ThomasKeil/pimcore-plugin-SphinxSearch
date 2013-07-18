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


class SphinxSearch_DocumentController extends Pimcore_Controller_Action_Admin {

  /**
   * @var $config Zend_Config_Xml
   */
  private $plugin_config;

  /**
   * @var $classes SphinxSearch_Config
   */
  private $config;

  public function init() {
    parent::init();
    $this->config = new SphinxSearch_Config();
    $this->plugin_config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications
  }



  public function saveAction() {
    $data = json_decode($this->_getParam("data"), true);

    $document_id = $data["id"];
    $document = Document_Page::getById($document_id);

    $controller = $document->getController();
    $action = $document->getAction();
    $template = $document->getTemplate();

    $config_name = $controller."_".$action;
    if ($template != "") $config_name."_".$template;

    $documents = $this->config->getDocuments();

    unset($documents->$config_name);
    if (sizeof($data["config"]) > 0) {
      $document_config = $documents->addChild($config_name);
      $document_config->addAttribute("controller", $controller);
      $document_config->addAttribute("action", $action);
      $document_config->addAttribute("template", $template);

      foreach ($data["config"] as $name => $values) {
        switch($values["type"]) {
          case "textarea":
          case "wysiwyg":
            $node = $document_config->addChild($name);
            $node->addAttribute("type", $values["type"]);
            $node->addAttribute("weight", $values["weight"]);
            break;
          default:
            break;
        }
      }
    }

    $this->config->writeXml();
    $this->config->writeSphinxConfig();

    $this->_helper->json(array("success" => true, ));

  }

  private function setSphinxValues($class_config, $class_name, $class_xml) {
    if (array_key_exists("childs", $class_config) && is_array($class_config["childs"])) {

      $filter = new Zend_Filter_Int();

      foreach ($class_config["childs"] as $child) {
        $this->setSphinxValues($child, $class_name, $class_xml);
        if ($child["index_sphinx"] == 1) {
          $weight = $filter->filter($child["weight_sphinx"]);
          switch ($child["fieldtype"]) {
            case "input":
            case "checkbox":
            case "date":
            case "datetime":
            case "numeric":
            case "textarea":
            case "time":
            case "wysiwyg":
              $node = $class_xml->$class_name->addChild($child["fieldtype"]);
              $node->addAttribute("name", $child["name"]);
              if ($weight > 1) { // 1 is default
                $node->addAttribute("weight", $weight);
              }
              break;
            default:
              break;
          }
        }
      }
    }
  }
}