<?php

/**
 * @file
 * Contains \Drupal\publication_date\Tests\PublicationDateTest.
 */

namespace Drupal\publication_date\Tests;

use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for publication_date.
 *
 * @group publication_date
 */
class PublicationDateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'user', 'publication_date');

  protected $privileged_user;

  public function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $this->privileged_user = $this->drupalCreateUser(array(
      'create page content',
      'edit own page content',
      'administer nodes',
      'set page published on date',
    ));
    $this->drupalLogin($this->privileged_user);
  }

  /**
   * Test automatic saving of variables.
   */
  public function testActionSaving() {

    // Create node to edit.
    $node = $this->drupalCreateNode(array('status' => 0));
    $unpublished_node = node_load($node->id());
    $value = $unpublished_node->published_at->value;
    $this->assertEqual($unpublished_node->published_at->value, PUBLICATION_DATE_DEFAULT);
    $this->assertEqual($unpublished_node->published_at->published_at_or_now, REQUEST_TIME, 'Published at or now date is REQUEST_TIME');

    // Publish the node.
    $unpublished_node->status = 1;
    $unpublished_node->save();
    $published_node = node_load($node->id());
    $this->assertTrue(is_numeric($published_node->published_at->value),
      'Published date is integer/numberic once published');
    $this->assertTrue($published_node->published_at->value == REQUEST_TIME,
      'Published date is REQUEST_TIME');
    $this->assertTrue($unpublished_node->published_at->published_at_or_now == $published_node->published_at->value,
      'Published at or now date equals published date');

    // Remember time.
    $time = $published_node->published_at->value;

    // Unpublish the node and check that the field value is maintained.
    $published_node->status = 0;
    $published_node->save();
    $unpublished_node = node_load($node->id());
    $this->assertTrue($unpublished_node->published_at->value == $time,
      'Published date is maintained when unpublished');

    // Set the field to zero and and make sure the published date is empty.
    $unpublished_node->published_at->value = 0;
    $unpublished_node->save();
    $unpublished_node = node_load($node->id());
    $this->assertEqual($unpublished_node->published_at->value, PUBLICATION_DATE_DEFAULT);

    // Set a custom time and make sure that it is saved.
    $time = $unpublished_node->published_at->value = 122630400;
    $unpublished_node->save();
    $unpublished_node = node_load($node->id());
    $this->assertTrue($unpublished_node->published_at->value == $time,
      'Custom published date is saved');
    $this->assertTrue($unpublished_node->published_at->published_at_or_now == $time,
      'Published at or now date equals published date');

    // Republish the node and check that the field value is maintained.
    $unpublished_node->status = 1;
    $unpublished_node->save();
    $published_node = node_load($node->id());
    $this->assertTrue($published_node->published_at->value == $time,
      'Custom published date is maintained when republished');

    // Set the field to zero and and make sure the published date is reset.
    $published_node->published_at->value = 0;
    $published_node->save();
    $published_node = node_load($node->id());
    $this->assertTrue($published_node->published_at->value > $time,
      'Published date is reset');

    // Now try it by purely pushing the forms around.

  }

  /**
   * Test automatic saving of variables via forms
   */
  public function testActionSavingOnForms() {
    $edit = array();
    $edit["title[0][value]"] = 'publication test node ' . $this->randomMachineName(10);
    $edit['status[value]'] = 1;

    // Hard to test created time == REQUEST_TIME because simpletest launches a
    // new HTTP session, so just check it's set.
    $this->drupalPostForm('node/add/page', $edit, (string) t('Save'));
    $node = $this->drupalGetNodeByTitle($edit["title[0][value]"]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $value = $this->getPubdateFieldValue();
    list($date, $time) = explode(' ', $value);

    // Make sure it was created with Published At set.
    $this->assertNotNull($value, t('Publication date set initially'));

    // Unpublish the node and check that the field value is maintained.
    $edit['status[value]'] = 0;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('published_at[0][value][date]', $date, t('Pubdate is maintained when unpublished'));
    $this->assertFieldByName('published_at[0][value][time]', $time, t('Pubdate is maintained when unpublished'));

    // Republish the node and check that the field value is maintained.
    $edit['status[value]'] = 1;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('published_at[0][value][date]', $date, t('Pubdate is maintained when republished'));
    $this->assertFieldByName('published_at[0][value][time]', $time, t('Pubdate is maintained when republished'));

    // Set a custom time and make sure that it is stored correctly.
    $ctime = REQUEST_TIME - 180;
    $edit['published_at[0][value][date]'] = format_date($ctime, 'custom', 'Y-m-d');
    $edit['published_at[0][value][time]'] = format_date($ctime, 'custom', 'H:i:s');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $value = $this->getPubdateFieldValue();
    list($date, $time) = explode(' ', $value);
    $this->assertEqual($date, format_date($ctime, 'custom', 'Y-m-d'), t('Custom date was set'));
    $this->assertEqual($time, format_date($ctime, 'custom', 'H:i:s'), t('Custom time was set'));

    // Set the field to empty and and make sure the published date is reset.
    $edit['published_at[0][value][date]'] = '';
    $edit['published_at[0][value][time]'] = '';
    sleep(2);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $new_value = $this->getPubdateFieldValue();
    list($new_date, $new_time) = explode(' ', $this->getPubdateFieldValue());
    $this->assertNotNull($new_value, t('Published time was set automatically when there was no value entered'));
    $this->assertNotEqual($new_time, $time, t('The new published-at time is different from the custom time'));
    $this->assertTrue(strtotime($this->getPubdateFieldValue()) > strtotime($value), t('The new published-at time is greater than the original one'));

    // Unpublish the node.
    $edit['status[value]'] = 0;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));

    // Set the field to empty and and make sure that it stays empty.
    $edit['published_at[0][value][date]'] = '';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, (string) t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('published_at[0][value][date]', '',
      t('Publication date field is empty'));
  }

  // Test that it cares about setting the published_at field.
  // This is useful for people using 'migrate' etc.
  public function testActionSavingSetDate() {
    $node = $this->drupalCreateNode(array('status' => 0));
    $unpublished_node = node_load($node->id());
    $this->assertEqual($unpublished_node->published_at->value, PUBLICATION_DATE_DEFAULT);

    // Now publish this with our custom time...
    $unpublished_node->status = 1;
    $static_time = 12345678;
    $unpublished_node->published_at->value = $static_time;
    $unpublished_node->save();
    $published_node = node_load($node->id());
    // ...and see if it comes back with it correctly.
    $this->assertTrue(is_numeric($published_node->published_at->value),
      'Published date is integer/numberic once published');
    $this->assertTrue($published_node->published_at->value == $static_time,
      'Published date is set to what we expected');
  }

  /**
   * Returns the value of our published-at field
   * @return string
   */
  private function getPubdateFieldValue() {
    $value = '';

    if ($this->assertField('published_at[0][value][date]', t('Published At field exists'))) {
      $field = $this->xpath('//input[@name="published_at[0][value][date]"]');
      $date = (string) $field[0]['value'];
      $field = $this->xpath('//input[@name="published_at[0][value][time]"]');
      $time = (string) $field[0]['value'];
      return trim($date . ' ' . $time);
    }

    return $value;
  }

}
