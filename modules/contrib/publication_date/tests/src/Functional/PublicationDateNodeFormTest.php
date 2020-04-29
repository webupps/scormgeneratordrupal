<?php

namespace Drupal\Tests\publication_date\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the integration on node forms.
 *
 * @group publication_date
 */
class PublicationDateNodeFormTest extends BrowserTestBase {

  use \Drupal\Tests\node\Traits\ContentTypeCreationTrait;

  /**
   * Modules to enable.
   */
  protected static $modules = [
    'node',
    'publication_date'
  ];

  protected function setUp() {
    parent::setUp();

    // Unpublished by default.
    $nodeType = NodeType::create([
      'type' => 'test1',
      'name' => 'Test Unpublished'
    ]);
    $nodeType->save();
    $entity = BaseFieldOverride::create([
      'field_name' => 'status',
      'entity_type' => 'node',
      'bundle' => 'test1',
    ]);
    $entity->setDefaultValue(FALSE)->save();

    $nodeType = NodeType::create([
      'type' => 'test2',
      'name' => 'Test Published'
    ]);
    $nodeType->save();
    $entity = BaseFieldOverride::create([
      'field_name' => 'status',
      'entity_type' => 'node',
      'bundle' => 'test2',
    ]);
    $entity->setDefaultValue(TRUE)->save();

    $account = $this->drupalCreateUser([
      'create test1 content',
      'create test2 content',
      'edit own test1 content',
      'edit own test2 content',
      'set test1 published on date',
      'set test2 published on date',
      'administer nodes',
    ]);
    $this->drupalLogin($account);
  }

  public function testNodeForm() {
    // Unpublished by default
    $title = $this->randomString() . '1';
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => 'test1']));
    $this->assertSession()->fieldValueEquals('published_at[0][value][date]', '');
    $this->assertSession()->fieldValueEquals('published_at[0][value][time]', '');
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->findButton('Save')->submit();
    $node = $this->getNodeByTitle($title);
    $this->assertEquals($node->published_at->value, PUBLICATION_DATE_DEFAULT);

    // Published by default
    $title = $this->randomString() . '2';
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => 'test2']));
    $this->assertSession()->fieldValueEquals('published_at[0][value][date]', '');
    $this->assertSession()->fieldValueEquals('published_at[0][value][time]', '');
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->findButton('Save')->submit();
    $node = $this->getNodeByTitle($title);
    $this->assertNotEmpty($node->published_at->value);
    $this->assertNotEquals($node->published_at->value, PUBLICATION_DATE_DEFAULT);
  }

}
