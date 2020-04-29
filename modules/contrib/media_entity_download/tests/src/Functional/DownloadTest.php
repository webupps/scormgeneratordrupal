<?php

namespace Drupal\Tests\media_entity_download\Functional;

use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestCreateMediaTypeTrait;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group media_entity_download
 */
class DownloadTest extends BrowserTestBase {

  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'media',
    'file',
    'media_entity_download',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['download media']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testDownload() {
    // Create bundle and modify form display.
    $media_type = $this->createMediaType(['bundle' => 'document'], 'file');
    $fieldDefinition = $media_type->getSource()->getSourceFieldDefinition($media_type);
    $sourceField = $fieldDefinition->getName();
    $value = FileItem::generateSampleValue($fieldDefinition);
    $media = Media::create([
      'name' => 'test',
      'bundle' => 'document',
      $sourceField => $value['target_id'],
    ]);
    $media->save();

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertResponse(200);
  }

}
