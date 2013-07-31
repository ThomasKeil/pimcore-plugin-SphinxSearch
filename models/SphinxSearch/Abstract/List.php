<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 04.07.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

// http://framework.zend.com/manual/1.12/de/zend.paginator.advanced.html#zend.paginator.advanced.adapters

abstract class SphinxSearch_Abstract_List implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

  protected $offset = 0;
  protected $limit = 0;

  protected $order_key = "oo_id";
  protected $order = "ASC";

  /**
   * @var SphinxClient
   */
  protected $SphinxClient;

  /**
   * @var string The query to use when using the API'ss query-functionality
   */
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

  /**
   * @var boolean
   */
  public $unpublished = false;


  public function __construct($query) {
    $sphinx_config = SphinxSearch_Config::getInstance();
    $this->query = $query;

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

  }

  protected abstract function load();

  /**
   * @param  $order
   * @return SphinxSearch_ListAbstract
   */
  public function setOrder($order) {
    $order = strtoupper($order);
    if ($order != "ASC") $order = "DESC";
    $this->order = $order;
    $this->SphinxClient->SetSortMode(SPH_SORT_EXTENDED, $this->order_key." ".$this->order);
    return $this;
  }

  /**
   * @return string
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * @param string $order_key
   * @return SphinxSearch_ListAbstract
   */
  public function setOrderKey($order_key) {
    $this->order_key = $order_key;
    $this->SphinxClient->SetSortMode(SPH_SORT_EXTENDED, $this->order_key." ".$this->order);
    return $this;
  }

  /**
   * @return array|string
   */
  public function getOrderKey() {
    return $this->order_key;
  }

  /**
   * @param $offset
   * @return SphinxSearch_ListAbstract
   */
  public function setOffset($offset) {
    $this->offset = $offset;
    return $this;
  }

  public function setLimit($limit) {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Sets the select clause, listing specific attributes to fetch, and expressions to compute and fetch.
   *
   * @param $clause SQL-like clause.
   * @return SphinxSearch_ListAbstract
   * @throws Exception
   */
  public function setSelect($clause) {
    $result = $this->SphinxClient->SetSelect($clause);
    if ($result === false) {
      throw new Exception("Error on setting select \"".$clause."\":\n".$this->SphinxClient->GetLastError());
    }
    return $this;
  }

  /**
   * Adds new integer values set filter to the existing list of filters.
   *
   * @param $attribute An attribute name.
   * @param $values Plain array of integer values.
   * @param bool $exclude If set to TRUE, matching items are excluded from the result set.
   * @return SphinxSearch_ListAbstract
   * @throws Exception on failure
   */
  public function setFilter($attribute, $values, $exclude = false) {
    $result = $this->SphinxClient->SetFilter($attribute, $values, $exclude);
    if ($result === false) {
      throw new Exception("Error on setting filter \"".$attribute."\":\n".$this->SphinxClient->GetLastError());
    }
    return $this;
  }

  /**
   * @return bool
   */
  public function getUnpublished() {
    return $this->unpublished;
  }

  /**
   * @return SphinxSearch_ListAbstract
   */
  public function setUnpublished($unpublished) {
    $this->unpublished = (bool) $unpublished;
    return $this;
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