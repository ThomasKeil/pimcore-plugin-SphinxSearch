<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 04.07.13
 * Time: 09:20
 * To change this template use File | Settings | File Templates.
 */

// http://framework.zend.com/manual/1.12/de/zend.paginator.advanced.html#zend.paginator.advanced.adapters

class SphinxSearch_DocumentList implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {


  public function __construct() {

  }

  /**
   * Returns an collection of items for a page.
   *
   * @param  integer $offset Page offset
   * @param  integer $itemCountPerPage Number of items per page
   * @return array
   */
  public function getItems($offset, $itemCountPerPage) {

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

  }

  /**
   * Return a fully configured Paginator Adapter from this method.
   *
   * @return Zend_Paginator_Adapter_Interface
   */
  public function getPaginatorAdapter() {

  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   * @return mixed Can return any type.
   */
  public function current() {

  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   * @return void Any returned value is ignored.
   */
  public function next() {

  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   * @return mixed scalar on success, or null on failure.
   */
  public function key() {

  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean The return value will be casted to boolean and then evaluated.
   * Returns true on success or false on failure.
   */
  public function valid() {

  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Rewind the Iterator to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   * @return void Any returned value is ignored.
   */
  public function rewind() {
    
  }

}