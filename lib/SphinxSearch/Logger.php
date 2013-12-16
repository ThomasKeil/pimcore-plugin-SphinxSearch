<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 16.12.13
 * Time: 08:25
 */

class SphinxSearch_Logger {

  /**
   * $l is for backward compatibility
   **/

  public static function emergency ($m, $l = null) {
    Logger::emergency("[ShinxSearch] ".$m, $l);
  }

  public static function emerg ($m, $l = null) {
    Logger::emerg("[ShinxSearch] ".$m, $l);
  }

  public static function critical ($m, $l = null) {
    Logger::critical("[ShinxSearch] ".$m, $l);
  }

  public static function crit ($m, $l = null) {
    Logger::crit("[ShinxSearch] ".$m, $l);
  }

  public static function error ($m, $l = null) {
    Logger::error("[ShinxSearch] ".$m, $l);
  }

  public static function err ($m, $l = null) {
    Logger::err("[ShinxSearch] ".$m, $l);
  }

  public static function alert ($m, $l = null) {
    Logger::alert("[ShinxSearch] ".$m, $l);
  }

  public static function warning ($m, $l = null) {
    Logger::warning("[ShinxSearch] ".$m, $l);
  }

  public static function warn ($m, $l = null) {
    Logger::warn("[ShinxSearch] ".$m, $l);
  }

  public static function notice ($m, $l = null) {
    Logger::notice("[ShinxSearch] ".$m, $l);
  }

  public static function info ($m, $l = null) {
    Logger::info("[ShinxSearch] ".$m, $l);
  }

  public static function debug ($m, $l = null) {
    Logger::debug("[ShinxSearch] ".$m, $l);
  }


}