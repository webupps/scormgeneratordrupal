<?php

/**
 * @file
 * Contains \Drupal\skinr\Tests\SkinrWebTestBase.
 */

namespace Drupal\skinr\Tests;

use Drupal\simpletest\WebTestBase;

class SkinrWebTestBase extends WebTestBase {
  /**
   * Asserts that a class is set for the given element id.
   *
   * @param $id
   *   Id of the HTML element to check.
   * @param $class
   *   The class name to check for.
   * @param $message
   *   Message to display.
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  function assertSkinrClass($id, $class, $message = '') {
    $elements = $this->xpath('//div[@id=:id and contains(@class, :class)]', array(
      ':id' => $id,
      ':class' => $class,
    ));
    $this->assertTrue(!empty($elements[0]), $message);
  }

  /**
   * Asserts that a class is not set for the given element id.
   *
   * @param $id
   *   Id of the HTML element to check.
   * @param $class
   *   The class name to check for.
   * @param $message
   *   Message to display.
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoSkinrClass($id, $class, $message = '') {
    $elements = $this->xpath('//div[@id=:id]', array(':id' => $id));
    $class_attr = (string) $elements[0]['class'];
    $this->assertTrue(strpos($class_attr, $class) === FALSE, $message);
  }

  /**
   * Pass if the message $text was set by one of the CRUD hooks in
   * skinr_test.module, i.e., if the $text is an element of
   * $_SESSION['skinr_test'].
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertHookMessage($text, $message = NULL, $group = 'Other') {
    if (!isset($message)) {
      $message = $text;
    }
    return $this->assertTrue(array_search($text, $_SESSION['skinr_test']) !== FALSE, $message, $group);
  }
}
