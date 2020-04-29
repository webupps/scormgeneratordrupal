<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

/**
 * @FieldFormatter(
 *  id = "iframe_default",
 *  label = @Translation("Title, over iframe (default)"),
 *  field_types = {"iframe"}
 * )
 */
class IframeDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'url' => '',
      'title' => '',
      'width' => '',
      'height' => '',
      'class' => '',
      'expose_class' => '',
      'frameborder' => '0',
      'scrolling' => 'auto',
      'transparency' => '0',
      'tokensupport' => '0',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  /* Settings form after "manage form display" page, valid for one field of content type */
  /* USE only if any further specific-Formatter-fields needed */
  /*
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $sizedescription = t('Iframes need a fixed width and height; only numbers are allowed.');
    $element['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Iframe Width'),
      '#default_value' => $this->getSetting('width'), # ''
      '#description' => $sizedescription,
      '#maxlength' => 4,
      '#size' => 4,
    );
    $element['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Iframe Height'),
      '#default_value' => $this->getSetting('height'), # ''
      '#description' => $sizedescription,
      '#maxlength' => 4,
      '#size' => 4,
    );
    $element['class'] = array(
      '#type' => 'textfield',
      '#title' => t('Additional CSS Class'),
      '#default_value' => $this->getSetting('class'), # ''
      '#description' => t('When output, this iframe will have this class attribute. Multiple classes should be separated by spaces.'),
    );
    return $element;
  }
  */

  /**
   * {@inheritdoc}
   */
  /* summary on the "manage display" page, valid for one content type */
  /*
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Iframe default width: @width', array('@width' => $this->getSetting('width')));
    $summary[] = t('Iframe default height: @height', array('@height' => $this->getSetting('height')));

    return $summary;
  }
  */


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    $entity = $items->getEntity();
    #\iframe_debug(3, __METHOD__, $entity);
    #\iframe_debug(3, __METHOD__, $settings);
    #\iframe_debug(3, __METHOD__, $field_settings);
    #\iframe_debug(3, __METHOD__, $items->getValue());


    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      if (!isset($item->title)) {
        $item->title = '';
      }
      $elements[$delta] = array(
        '#markup' => self::iframe_iframe($item->title, $item->url, $item),
        '#allowed_tags' => array('iframe', 'a', 'h3'),
      );
      # tokens can be dynamic, so its not cacheable
      if (isset($settings['tokensupport']) && $settings['tokensupport']) {
        $elements[$delta]['cache'] = array('max-age' => 0);
      }
    }
    return $elements;
  }

  /*
   * like central function
   * form the iframe code
   */
  static public function iframe_iframe($text, $path, $item) {
    $options = array();
    $options['width'] = !empty($item->width)? $item->width : '100%';
    $options['height'] = !empty($item->height)? $item->height : '701';

    if (!empty($item->frameborder) && $item->frameborder > 0) {
        $options['frameborder'] = (int)$item->frameborder;
    }
    else {
        $options['frameborder'] = 0;
    }
    $options['scrolling'] = !empty($item->scrolling) ? $item->scrolling : 'auto';
    if (!empty($item->transparency) && $item->transparency > 0) {
        $options['transparency'] = (int)$item->transparency;
    }
    else {
        $options['transparency'] = 0;
    }

    $htmlid = '';
    if (isset($item->htmlid) && !empty($item->htmlid)) {
      $htmlid = ' id="' . htmlspecialchars($item->htmlid) . '" name="' . htmlspecialchars($item->htmlid) . '"';
    }

    // Append active class.
    $options['class'] = !empty($item->class) ? $item->class : '';

    // Remove all HTML and PHP tags from a tooltip. For best performance, we act only
    // if a quick strpos() pre-check gave a suspicion (because strip_tags() is expensive).
    $options['title'] = !empty($item->title) ? $item->title : '';
    if (!empty($options['title']) && strpos($options['title'], '<') !== FALSE) {
      $options['title'] = strip_tags($options['title']);
    }
    $options_link = array(); $options_link['attributes'] = array();
    $options_link['attributes']['title'] = $options['title'];

    $drupal_attributes = new Attribute($options);

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      // Token Support for field "url" and "title"
      $tokensupport = $item->getTokenSupport();
      $tokencontext = array('user' => \Drupal::currentUser());
      if (isset($GLOBALS['node'])) {
        $tokencontext['node'] = $GLOBALS['node'];
      }
      if ($tokensupport > 0) {
        $text = \Drupal::token()->replace($text, $tokencontext);
      }
      if ($tokensupport > 1) {
        $path = \Drupal::token()->replace($path, $tokencontext);
      }
    }

    $output =
      '<div class="' . (!empty($options['class'])? new HtmlEscapedText($options['class']) : '') . '">'
        . (empty($text)? '' : '<h3 class="iframe_title">' . (isset($options['html']) && $options['html'] ? $text : new HtmlEscapedText($text)) . '</h3>')
        . '<iframe src="' . htmlspecialchars(Url::fromUri($path, $options)->toString()) . '"'
          . $drupal_attributes->__toString()
          . $htmlid
        . '>'
        . t('Your browser does not support iframes, but you can use the following link:') . ' ' . Link::fromTextAndUrl('Link', Url::fromUri($path, $options_link))->toString()
        . '</iframe>'
      . '</div>'
    ;
    return $output;
  }


}
