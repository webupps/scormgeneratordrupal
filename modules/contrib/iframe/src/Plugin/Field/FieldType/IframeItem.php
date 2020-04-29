<?php

namespace Drupal\iframe\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
#use Drupal\Core\Url;
#use Drupal\link\LinkItemInterface;
use Drupal\Component\Utility\Random;

/**
 * Plugin implementation of the 'Iframe' field type.
 *
 * @FieldType(
 *   id = "iframe",
 *   label = @Translation("Iframe"),
 *   description = @Translation("The Iframe module defines an iframe field type for the Field module. Further definable are attributes for styling the iframe, like: URL, width, height, title, class, frameborder, scrolling and transparency."),
 *   default_widget = "iframe_urlwidthheight",
 *   default_formatter = "iframe_default"
 * )
 */

class IframeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'title' => '',
      'class' => '',
      'height' => '',
      'width' => '',
      'frameborder' => 0,
      'scrolling' => 'auto',
      'transparency' => 0,
      'tokensupport' => 0,
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['url'] = DataDefinition::create('uri')
      ->setLabel(t('URL'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title text'));

    $properties['width'] = DataDefinition::create('string')
      ->setLabel(t('Width'));

    $properties['height'] = DataDefinition::create('string')
      ->setLabel(t('Height'));

    $properties['class'] = DataDefinition::create('string')
      ->setLabel(t('Css class'));

    $properties['frameborder'] = DataDefinition::create('string')
      ->setLabel(t('Frameborder'));

    $properties['scrolling'] = DataDefinition::create('string')
      ->setLabel(t('Scrolling'));

    $properties['transparency'] = DataDefinition::create('string')
      ->setLabel(t('Transparency'));

    $properties['tokensupport'] = DataDefinition::create('string')
      ->setLabel(t('Token support'));

    return $properties;
  }

  /**
   * Implements hook_field_schema().
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'url' => array(
          'description' => 'The URL of the iframe.',
          'type' => 'varchar',
          'length' => 2048,
          'not null' => FALSE,
          'sortable' => TRUE,
          'default' => '',
        ),
        'title' => array(
          'description' => 'The iframe title text.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'sortable' => TRUE,
          'default' => '',
        ),
        'class' => array(
          'description' => 'When output, this iframe will have this CSS class attribute. Multiple classes should be separated by spaces.',
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
          'default' => '',
        ),
        'width' => array(
          'description' => 'The iframe width.',
          'type' => 'varchar',
          'length' => 4,
          'not null' => FALSE,
          'default' => '600',
        ),
        'height' => array(
          'description' => 'The iframe height.',
          'type' => 'varchar',
          'length' => 4,
          'not null' => FALSE,
          'default' => '800',
        ),
        'frameborder' => array(
          'description' => 'Frameborder is the border around the iframe. Most people want it removed, so the default value for frameborder is zero (0), or no border.',
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
        'scrolling' => array(
          'description' => 'Scrollbars help the user to reach all iframe content despite the real height of the iframe content. Please disable it only if you know what you are doing.',
          'type' => 'varchar',
          'length' => 4,
          'not null' => TRUE,
          'default' => 'auto',
        ),
        'transparency' => array(
          'description' => 'Allow transparency per CSS in the outer iframe tag. You have to set background-color:transparent in your iframe body tag too!',
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
        'tokensupport' => array(
          'description' => 'Are tokens allowed for users to use in title or URL field?',
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'url' => array('url'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();

    $element['class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CSS Class'),
      '#default_value' => $settings['class'], # ''
    );

    $element['frameborder'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Frameborder'),
      '#default_value' => $settings['frameborder'], # '0'
      '#options' => array(
        '0' => $this->t('No frameborder'),
        '1' => $this->t('Show frameborder'),
      ),
    );

    $element['scrolling'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Scrolling'),
      '#default_value' => $settings['scrolling'], # 'auto'
      '#options' => array(
        'auto' => $this->t('Automatic'),
        'no' => $this->t('Disabled'),
        'yes' => $this->t('Enabled'),
      )
    );

    $element['transparency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Transparency'),
      '#default_value' => $settings['transparency'], # '0'
      '#options' => array(
        '0' => $this->t('No transparency'),
        '1' => $this->t('Allow transparency'),
      ),
      '#description' => $this->t('Allow transparency per CSS in the outer iframe tag. You have to set background-color:transparent in your iframe body tag too!'),
    );

    $element['tokensupport'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Token Support'),
      '#default_value' => $settings['tokensupport'], # '0'
      '#options' => array(
        '0' => $this->t('No tokens allowed'),
        '1' => $this->t('Tokens only in title field'),
        '2' => $this->t('Tokens for title and URL field'),
      ),
      '#description' => $this->t('Are tokens allowed for users to use in title or URL field?'),
    );
    if (! \Drupal::moduleHandler()->moduleExists('token')) {
      $element['tokensupport']['#description'] .= ' ' . t('Attention: Token module is not currently enabled!');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Set of possible top-level domains.
    $tlds = array('com', 'net', 'gov', 'org', 'edu', 'biz', 'info');
    // Set random length for the domain name.
    $domain_length = mt_rand(7, 15);
    $random = new Random();

    switch ($field_definition->getSetting('title')) {
      case DRUPAL_DISABLED:
        $values['title'] = '';
        break;
      case DRUPAL_REQUIRED:
        $values['title'] = $random->sentences(4);
        break;
      case DRUPAL_OPTIONAL:
        // In case of optional title, randomize its generation.
        $values['title'] = mt_rand(0,1) ? $random->sentences(4) : '';
        break;
    }
    $values['url'] = 'https://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds)-1))];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'url';
  }

  /**
   * Get token support setting.
   */
  public function getTokenSupport() {
    $value = $this->getSetting('tokensupport');
    $value = empty($value) ? 0 : (int) $value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  #public function getUrl() {
  #  return Url::fromUri($this->url);
  #}


}

