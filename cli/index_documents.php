#!/usr/bin/php
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

ini_set('memory_limit', '2048M');
set_time_limit(-1);
date_default_timezone_set("Europe/Berlin");

include_once(dirname(__FILE__)."/../../../pimcore/config/startup.php");
Pimcore::initAutoloader();
Pimcore::initConfiguration();
Pimcore::initLogger();
Pimcore::initPlugins();

$opts = new Zend_Console_Getopt(array(
  'language|l=s' => "language",
  'document|d=s' => "document"
));

try {
  $opts->parse();
} catch (Exception $e) {
  die ("Fehler: ".$e->getMessage());
}

$sphinx_config = SphinxSearch_Config::getInstance();

$documents = $sphinx_config->getDocumentsAsArray();

if (!array_key_exists($opts->document, $documents)) {
  print "Unknown document: ".$opts->document."\n";
  print "Possible documents are:\n";
  foreach ($documents as $document_name => $document_config) {
    print $document_name."\n";
  }
  die();
}

$document_config = $documents[$opts->document];

$controller = $document_config["controller"];
$action = $document_config["action"];
$template = $document_config["template"];


$db = Pimcore_Resource::get();

$query = "SELECT * FROM documents_page WHERE `controller` = \"".$controller."\" AND `action` = \"".$action."\"";
if (is_null($template) || $template == "") {
  $query .= " AND `template` IS NULL";
} else {
  $query .= " AND `template` = \"".$template."\"";
}

$document_results = $db->fetchAll($query);

print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
print "<sphinx:docset>\n";

print "  <sphinx:schema>\n";
foreach ($document_config["elements"] as $name => $element) {
  print "    <sphinx:field name=\"".$name."\"/>\n";
}

print "  </sphinx:schema>\n";

foreach ($document_results as $document_result) {
  try {
    $document = Document_Page::getById($document_result["id"]);
    if ($opts->language != "all" && $document->getProperty("language") != $opts->language ) continue;
    print "\n  <sphinx:document id=\"".$document->getId()."\">\n";
    print "<o_published>".($document->getPublished() ? "1" : "0")."</o_published>\n";
    foreach ($document_config["elements"] as $element_name => $element_config) {
      $element = $document->getElement($element_name);
      if (is_null($element)) {
        print "    <".$element_name."></".$element_name.">\n";
      } else {
        switch (get_class($element)) {
          case "Document_Tag_Textarea":
          case "Document_Tag_Wysiwyg":
            print "    <".$element_name."><![CDATA[[".$element->text."]]></".$element_name.">\n";
            break;
          default:
            var_dump($element);
            break;
        }
      }
    }
  } catch (Exception $e) {

  }
  //print $document->getFullPath()."\n";
  print "  </sphinx:document>\n";
}


print "</sphinx:docset>\n";
