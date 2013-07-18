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
		$settings = array(
      "pid" => $config->path->pid,
      "logfile" => $config->path->log,
      "querylog" => $config->path->querylog,
      "indexer" => $config->path->indexer,
      "indexer_maintenance" => $config->indexer->runwithmaintenance,
      "indexer_period" => $config->indexer->period
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
      $config->indexer->period = $values["sphinxsearch.indexer_period"];
      $config->indexer->runwithmaintenance = $values["sphinxsearch.indexer_maintenance"];
      $config->documents->use_i18n = $values["sphinxsearch.documents_i18n"] == "true" ? "true" : "false";

      $writer = new Zend_Config_Writer_Xml(array(
        "config" => $config,
        "filename" => SPHINX_VAR.DIRECTORY_SEPARATOR."config.xml"
      ));
      $writer->write();


      $this->_helper->json(array("success" => true));
    } catch (Exception $e) {
      $this->_helper->json(false);
    }


  }
}