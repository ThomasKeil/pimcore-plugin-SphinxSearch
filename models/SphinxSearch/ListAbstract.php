<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 04.07.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

// http://framework.zend.com/manual/1.12/de/zend.paginator.advanced.html#zend.paginator.advanced.adapters

abstract class SphinxSearch_ListAbstract implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

  protected $offset = 0;
  protected $limit = 0;

  protected $order_key = "oo_id";
  protected $order = "ASC";

  protected $SphinxClient;

  protected $class_name;

  protected $query = "";

  protected $pointer = 0;

  protected $plugin_config;

  /**
   * @var bool|array
   */
  protected $search_result_ids = null;

  /**
   * @var bool|array
   */
  protected $search_result_items = null;


  public function __construct($class_name, $query = null) {
    $this->setQuery($query);

    $sphinx_config = SphinxSearch_Config::getInstance();
    $class_config = $sphinx_config->getClassesAsArray(); // The configuration

    $this->plugin_config = $sphinx_config->getConfig();

    $max_results = intval($this->plugin_config->maxresults);
    $this->limit = $max_results;

    $SphinxClient = new SphinxClient();
    $this->SphinxClient = $SphinxClient;

    $SphinxClient->SetMatchMode(SPH_MATCH_EXTENDED2);
    $SphinxClient->SetSortMode(SPH_SORT_EXTENDED, "@weight DESC");
    $SphinxClient->setServer("localhost", $this->plugin_config->searchd->port);

    // Sphinx Client is to always return everything - it's just IDs
    // Paginator is then to cast the necessary Items, this can be done
    // with offset/limit
    $SphinxClient->setLimits(0, $max_results, $max_results);

    $field_weights = array();
    foreach ($class_config[strtolower($class_name)] as $field_name => $field_config) {
      if (array_key_exists("weight", $field_config)) {
        $field_weights[$field_name] = $field_config["weight"];
      }
    }
    if (sizeof($field_weights) > 0) $SphinxClient->setFieldWeights($field_weights);

    $this->class_name = $class_name;
  }

  public function setQuery($query) {
    $this->query = $query;
  }

  protected abstract function load();

  /**
   * @param  $order
   * @return void
   */
  public function setOrder($order) {
    $order = strtoupper($order);
    if ($order != "ASC") $order = "DESC";
    $this->order = $order;
    $this->SphinxClient->SetSortMode(SPH_SORT_EXTENDED, $this->order_key." ".$this->order);
  }

  /**
   * @return string
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * @param string $order_key
   * @return void
   */
  public function setOrderKey($order_key) {
    $this->order_key = $order_key;
    $this->SphinxClient->SetSortMode(SPH_SORT_EXTENDED, $this->order_key." ".$this->order);
  }

  /**
   * @return array|string
   */
  public function getOrderKey() {
    return $this->order_key;
  }

  public function setOffset($offset) {
    $this->offset = $offset;
  }

  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * Returns a collection of items for a page.
   *
   * @param  integer $offset Page offset
   * @param  integer $itemCountPerPage Number of items per page
   * @return array
   */
  public function getItems($offset, $itemCountPerPage) {
    $this->setLimit($itemCountPerPage);
    $this->setOffset($offset);
    $this->load(true);
    return $this->search_result_items;
  }


  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Count elements of an object
   * @link http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   */
  public function count() {
    return $this->getTotalCount();

  }

  public abstract function getTotalCount();

  /**
   * Return a fully configured Paginator Adapter from this method.
   *
   * @return Zend_Paginator_Adapter_Interface
   */
  public function getPaginatorAdapter() {
    return $this;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   * @return mixed Can return any type.
   */
  public abstract function current();

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   * @return void Any returned value is ignored.
   */
  public function next() {
    $this->pointer++;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   * @return mixed scalar on success, or null on failure.
   */
  public function key() {
    return $this->pointer;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean The return value will be casted to boolean and then evaluated.
   * Returns true on success or false on failure.
   */
  public function valid() {
    $this->load();
    return array_key_exists($this->pointer, $this->search_result_items);
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Rewind the Iterator to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   * @return void Any returned value is ignored.
   */
  public function rewind() {
    $this->pointer = 0;
  }

}