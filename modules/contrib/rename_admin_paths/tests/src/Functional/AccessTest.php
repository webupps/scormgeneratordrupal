<?php

namespace Drupal\Tests\rename_admin_paths\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group tests
 */
class AccessTest extends BrowserTestBase {

  protected static $modules = ['rename_admin_paths'];

  /**
   * Test that the admin is still protected after renaming it
   */
  public function testAdminNotAccessibleAfterRenaming() {
    $output = $this->drupalGet('admin');
    $this->assertContains('You are not authorized to access this page.', $output);

    $output = $this->drupalGet('admin/modules');
    $this->assertContains('You are not authorized to access this page.', $output);

    $admin = $this->drupalCreateUser(['administer modules', 'administer site configuration'], 'administrator', TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->submitForm([
      'admin_path' => 1,
      'admin_path_value' => 'backend',
      'user_path' => 0,
      'user_path_value' => 'member',
    ], 'Save configuration');

    $this->drupalLogout();

    $output = $this->drupalGet('backend');
    $this->assertContains('You are not authorized to access this page.', $output);

    $output = $this->drupalGet('backend/modules');
    $this->assertContains('You are not authorized to access this page.', $output);
  }
}
