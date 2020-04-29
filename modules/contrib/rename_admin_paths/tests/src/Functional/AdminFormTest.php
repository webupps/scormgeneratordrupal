<?php

namespace Drupal\Tests\rename_admin_paths\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group tests
 */
class AdminFormTest extends BrowserTestBase {

  protected static $modules = ['rename_admin_paths'];

  /**
   * Setup admin user.
   */
  protected function setUp() {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer modules', 'administer site configuration'], 'administrator', TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Test admin is able to view the settings form.
   */
  public function testViewForm() {
    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->assertRenameAdminPathFormIsVisible();

    $this->assertSession()->fieldValueEquals('admin_path_value', 'backend');
    $this->assertSession()->fieldValueEquals('user_path_value', 'member');
  }

  /**
   * Test /admin and /user paths no longer exist when they are changed to /backend and /member
   */
  public function testEnablePathReplacements() {
    $output = $this->drupalGet('user/1');
    $this->assertContains('Member for', $output);

    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->submitForm([
      'admin_path' => 1,
      'admin_path_value' => 'backend',
      'user_path' => 1,
      'user_path_value' => 'member',
    ], 'Save configuration');

    $this->assertRenameAdminPathFormIsVisible();

    $this->assertSession()->fieldValueEquals('admin_path_value', 'backend');
    $this->assertSession()->fieldValueEquals('user_path_value', 'member');

    $output = $this->drupalGet('user/1');
    $this->assertContains('The requested page could not be found.', $output);

    $output = $this->drupalGet('member/1');
    $this->assertContains('Member for', $output);
  }

  private function assertRenameAdminPathFormIsVisible() {
    $output = $this->getSession()->getPage()->getContent();
    $this->assertContains('Rename admin path', $output);
    $this->assertContains('Replace "admin" in admin path by', $output);
    $this->assertContains('Rename user path', $output);
    $this->assertContains('Replace "user" in user path by', $output);
  }
}
