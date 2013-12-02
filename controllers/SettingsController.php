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


class SphinxSearch_SettingsController extends Pimcore_Controller_Action {

	public function settingsAction() {
    $config = SphinxSearch_Config_Plugin::getInstance();
    $config_data = $config->getData();

    $lastrun = new Zend_Date($config_data["indexer"]["lastrun"]);

    $settings = array(
      "pid" => $config_data["path"]["pid"],
      "logfile" => $config_data["path"]["log"],
      "querylog" => $config_data["path"]["querylog"],
      "indexer" => $config_data["path"]["indexer"],
      "phpcli" => $config_data["path"]["phpcli"],
      "path_searchd" => $config_data["path"]["searchd"],
      "indexer_maintenance" => $config_data["indexer"]["runwithmaintenance"],
      "indexer_period" => $config_data["indexer"]["period"],
      "searchd_port" => $config_data["searchd"]["port"] > 0 ? $config_data["searchd"]["port"] : 9312,
      "documents_i18n" => $config_data["documents"]["use_i18n"] == "true",
      "indexer_lastrun" => $lastrun->get(Zend_Date::DATETIME),
      "indexer_onchange" => $config_data["indexer"]["onchange"] ? $config_data["indexer"]["onchange"] : "nothing",
      "searchd_running" => SphinxSearch_Plugin::isSearchdRunning()
    );
		
		$this->_helper->json($settings);
	}
	
  public function saveAction() {
    $values = Zend_Json::decode($this->getParam("data"));

    // convert all special characters to their entities so the xml writer can put it into the file
    $values = array_htmlspecialchars($values);
    try {
      $sphinx_config = new SphinxSearch_Config();
      $sphinx_config->writeSphinxConfig();

      $plugin_config = new SphinxSearch_Config_Plugin();
      $config_data = $plugin_config->getData();
      $config_data["path"]["pid"] = $values["sphinxsearch.path_pid"];
      $config_data["path"]["querylog"] = $values["sphinxsearch.path_querylog"];
      $config_data["path"]["log"] = $values["sphinxsearch.path_logfile"];
      $config_data["path"]["indexer"] = $values["sphinxsearch.path_indexer"];
      $config_data["path"]["phpcli"] = $values["sphinxsearch.path_phpcli"];
      $config_data["path"]["searchd"] = $values["sphinxsearch.path_searchd"];
      $config_data["indexer"]["period"] = $values["sphinxsearch.indexer_period"];
      $config_data["indexer"]["runwithmaintenance"] = $values["sphinxsearch.indexer_maintenance"] == "true" ? "true" : "false";
      $config_data["indexer"]["onchange"] = $values["sphinxsearch.indexer_onchange"];
      $config_data["documents"]["use_i18n"] = $values["sphinxsearch.documents_i18n"] == "true" ? "true" : "false";
      $config_data["searchd"]["port"] = $values["sphinxsearch.searchd_port"];
      $plugin_config->setData($config_data);
      $plugin_config->save();



      $this->_helper->json(array("success" => true));
    } catch (Exception $e) {
      $this->_helper->json(false);
    }
  }
}
