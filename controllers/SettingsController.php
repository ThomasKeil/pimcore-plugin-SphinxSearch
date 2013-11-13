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
    $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications

    $lastrun = new Zend_Date($config->indexer->lastrun);

    $settings = array(
      "pid" => $config->path->pid,
      "logfile" => $config->path->log,
      "querylog" => $config->path->querylog,
      "indexer" => $config->path->indexer,
      "phpcli" => $config->path->phpcli,
      "indexer_maintenance" => $config->indexer->runwithmaintenance,
      "indexer_period" => $config->indexer->period,
      "searchd_port" => $config->searchd->port > 0 ? $config->searchd->port : 9312,
      "documents_i18n" => $config->documents->use_i18n == "true",
      "indexer_lastrun" => $lastrun->get(Zend_Date::DATETIME),
      "indexer_onchange" => $config->indexer->onchange ? $config->indexer->onchange : "nothing"
    );
		
		$this->_helper->json($settings);
	}
	
  public function saveAction() {
    $values = Zend_Json::decode($this->getParam("data"));

    // convert all special characters to their entities so the xml writer can put it into the file
    $values = array_htmlspecialchars($values);
    try {
      $config = new Zend_Config_Xml(SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml", null, true); // Filname, section, allowModifications
      $config->path->pid = $values["sphinxsearch.path_pid"];
      $config->path->querylog = $values["sphinxsearch.path_querylog"];
      $config->path->log = $values["sphinxsearch.path_logfile"];
      $config->path->indexer = $values["sphinxsearch.path_indexer"];
      $config->path->phpcli= $values["sphinxsearch.path_phpcli"];
      $config->indexer->period = $values["sphinxsearch.indexer_period"];
      $config->indexer->runwithmaintenance = $values["sphinxsearch.indexer_maintenance"] == "true" ? "true" : "false";
      $config->indexer->onchange = $values["sphinxsearch.indexer_onchange"];
      $config->documents->use_i18n = $values["sphinxsearch.documents_i18n"] == "true" ? "true" : "false";
      $config->searchd->port = $values["sphinxsearch.searchd_port"];

      $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
      ));
      $writer->write();

      $sphinx_config = new SphinxSearch_Config();
      $sphinx_config->writeSphinxConfig();

      $this->_helper->json(array("success" => true));
    } catch (Exception $e) {
      $this->_helper->json(false);
    }


  }
}