<?php

namespace Drupal\Tests\unpublished_nodes_redirect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\unpublished_nodes_redirect\Utils\UnpublishedNodesRedirectUtils as Utils;

/**
 * Test for Unpublished nodes redirect module.
 *
 * @group unpublished_nodes_redirect
 */
class UnpublishedNodesRedirectTest extends UnitTestCase {

  /**
   *
   */
  protected function setUp() {
    $this->utils = new Utils();
    parent::setUp();
  }

  /**
   * Tests getting node type keys.
   */
  public function testGetNodeTypeKey() {
    $this->assertEquals('content_type_name_unpublished_redirect_path',
                        $this->utils->getNodeTypeKey('content_type_name'));
  }

  /**
   * Tests getting response code keys.
   */
  public function testGetResponseCodeKey() {
    $this->assertEquals('content_type_name_unpublished_redirect_response_code',
                      $this->utils->getResponseCodeKey('content_type_name'));
  }

  /**
   * Tests checks before redirect.
   */
  public function testChecksBeforeRedirect() {
    $this->assertTrue(TRUE, $this->utils->checksBeforeRedirect(0, TRUE, '', 'test/path', 301));
    $this->assertFalse(FALSE, $this->utils->checksBeforeRedirect(1, TRUE, '', 'test/path', 301));
    $this->assertFalse(FALSE, $this->utils->checksBeforeRedirect(0, FALSE, '', 'test/path', 301));
    $this->assertFalse(FALSE, $this->utils->checksBeforeRedirect(0, FALSE, '', '', 301));
    $this->assertFalse(FALSE, $this->utils->checksBeforeRedirect(0, FALSE, '', 'test/path', 0));
  }

}
