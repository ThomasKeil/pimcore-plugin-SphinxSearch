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


class SphinxSearch_AdminController extends Pimcore_Controller_Action {


  public function runindexerAction() {
    $output = SphinxSearch_Plugin::runIndexer();
    $this->_helper->json(array("success" => $output["return_var"] == 0, "message" => $output["output"]));
  }

  public function startsearchdAction() {
    $output = SphinxSearch_Plugin::startSearchd();
    $this->_helper->json(array("success" => $output["result"], "message" => $output["message"]));
  }

  public function stopsearchdAction() {
    $output = SphinxSearch_Plugin::stopSearchd();
    $this->_helper->json(array("success" => $output["result"], "message" => $output["message"]));
  }

}