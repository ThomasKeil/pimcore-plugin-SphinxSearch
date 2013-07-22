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

class SphinxSearch_ClassController extends Pimcore_Controller_Action_Admin {

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

  public function getAction() {
    $class = Object_Class::getById(intval($this->getParam("id")));
    $class->setFieldDefinitions(null);

    $class_name = $class->getName();
    $classes = $this->config->getClassesAsArray();

    $layout_definition = $class->getLayoutDefinitions();

    $this->getSphinxValues($layout_definition, $classes[$class_name]);
    $this->_helper->json($class);

  }

  private function getSphinxValues($layout_definition, $class_configuration) {
    if (!is_array($class_configuration)) return;
    if (method_exists($layout_definition,"getChilds")) {
      foreach ($layout_definition->getChilds() as $child) {
        $this->getSphinxValues($child, $class_configuration);
      }
    }
    switch ($layout_definition->fieldtype) {
      case "input":
      case "checkbox":
      case "date":
      case "datetime":
      case "numeric":
      case "textarea":
      case "time":
      case "select":
      case "superboxselect": // DynamicDropdown Plugin
      case "wysiwyg":
        if (array_key_exists($layout_definition->name, $class_configuration)) {
          $layout_definition->index_sphinx = true;
          $layout_definition->weight_sphinx = intval($class_configuration[$layout_definition->name]["weight"]);
        }
        break;
    }

  }

  public function saveAction() {
    $class = Object_Class::getById(intval($this->getParam("id")));

    $class_config = Zend_Json::decode($this->getParam("configuration"));
    $class_name = $class->getName();

    $classes = $this->config->getClasses();

    unset($classes->$class_name);
    $classes->addChild($class_name);

    $this->setSphinxValues($class_config, $class_name, $classes);

    if ($classes->$class_name->count() == 0) unset($classes->$class_name); // remove empty classes

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
          $field_type = $child["fieldtype"];
          if (in_array($field_type, array("input", "checkbox", "date", "datetime", "numeric", "textarea", "time", "select", "superboxselect", "wysiwyg"))) {
            $node = $class_xml->$class_name->addChild($child["fieldtype"]);
            $node->addAttribute("name", $child["name"]);
            if ($weight > 1) { // 1 is default
              $node->addAttribute("weight", $weight);
            }

            switch ($field_type) {
              case "input":
              case "textarea":
              case "wysiwyg":
                $node->addAttribute("field_type", "sql_attr_string");
                break;
              case "date":
              case "datetime":
              case "time":
                $node->addAttribute("field_type", "sql_attr_timestamp");
                break;
              case "numeric":
                $node->addAttribute("field_type", "sql_attr_float");
                break;
            }

          }
        }
      }
    }
  }
}