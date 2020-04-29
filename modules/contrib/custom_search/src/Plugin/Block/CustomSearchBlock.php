<?php

namespace Drupal\custom_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Custom Search form' block.
 *
 * @Block(
 *   id = "custom_search",
 *   category = @Translation("Forms"),
 *   admin_label = @Translation("Custom Search form")
 * )
 */
class CustomSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorInterface
   */
  protected $errorHandler;

  /**
   * Constructs a new CustomSearchBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module handler object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
	  if ($account->hasPermission('search content')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = array(
      'search_box'  => array(
        'label_visibility'  => FALSE,
        'label'             => $this->t('Search this site'),
        'placeholder'       => '',
        'title'             => $this->t('Enter the terms you wish to search for.'),
        'size'              => 15,
        'max_length'        => 128,
        'weight'            => -9,
        'region'            => 'block',
      ),
      'submit'      => array(
        'text'        => $this->t('Search'),
        'image_path'  => '',
        'weight'      => 9,
        'region'      => 'block',
      ),
      'content'     => array(
        'types'     => array(),
        'other'     => array(),
        'selector'  => array(
          'type'             => 'select',
          'label_visibility' => TRUE,
          'label'            => $this->t('Search for'),
        ),
        'any'       => array(
          'text'      => $this->t('- Any -'),
          'restricts' => FALSE,
          'force'     => FALSE,
        ),
        'excluded'  => array(),
        'weight'    => -8,
        'region'    => 'block',
      ),
      'criteria'    => array(
        'or'        => array(
          'display' => FALSE,
          'label'   => $this->t('Containing any of the words'),
          'weight'  => 4,
          'region'  => 'block',
        ),
        'phrase'    => array(
          'display' => FALSE,
          'label'   => $this->t('Containing the phrase'),
          'weight'  => 5,
          'region'  => 'block',
        ),
        'negative'  => array(
          'display' => FALSE,
          'label'   => $this->t('Containing none of the words'),
          'weight'  => 6,
          'region'  => 'block',
        ),
      ),
      'languages' => array(
        'languages' => array(),
        'selector'  => array(
          'type'             => 'select',
          'label_visibility' => TRUE,
          'label'            => $this->t('Languages'),
        ),
        'any'       => array(
          'text'      => $this->t('- Any -'),
          'restricts' => FALSE,
          'force'     => FALSE,
        ),
        'weight'    => 7,
        'region'    => 'block',
      ),
      'paths'     => array(
        'list'            => '',
        'selector'        => array(
          'type'              => 'select',
          'label_visibility'  => TRUE,
          'label'             => $this->t('Customize your search'),
        ),
        'separator'       => '+',
        'weight'          => 8,
        'region'          => 'block',
      ),
    );
    
    $search_pages = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() == 'node_search' && $page->isDefaultSearch()) {
        $defaults['content']['page'] = $page->id();
        break;
      }
    }

    $vocabularies = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    $vocWeight = -7;
    foreach ($vocabularies as $voc) {
      $vocId = $voc->id();
      $defaults['taxonomy'][$vocId]['type'] = 'disabled';
      $defaults['taxonomy'][$vocId]['depth'] = 0;
      $defaults['taxonomy'][$vocId]['label_visibility'] = TRUE;
      $defaults['taxonomy'][$vocId]['label'] = $voc->label();
      $defaults['taxonomy'][$vocId]['all_text'] = t('- Any -');
      $defaults['taxonomy'][$vocId]['region'] = 'block';
      $defaults['taxonomy'][$vocId]['weight'] = $vocWeight;
      $vocWeight++;
    }

    return $defaults;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Labels & default text.
    $form['search_box'] = array(
      '#type'   => 'details',
      '#title'  => $this->t('Search box'),
      '#open'   => TRUE,
    );
    $form['search_box']['label_visibility'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display label'),
      '#default_value'  => $this->configuration['search_box']['label_visibility'],
    );
    $form['search_box']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label'),
      '#default_value'  => $this->configuration['search_box']['label'],
      '#description'    => $this->t('Enter the label text for the search box. The default value is "Search this site".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[search_box][label_visibility]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['search_box']['placeholder'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Placeholder text'),
      '#default_value'  => $this->configuration['search_box']['placeholder'],
      '#description'    => $this->t('This will change the default text inside the search form. This is the <a href="http://www.w3schools.com/tags/att_input_placeholder.asp" target="_blank">placeholder</a> attribute for the TextField. Leave blank for no text. This field is blank by default.'),
    );
    $form['search_box']['title'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Hint text'),
      '#default_value'  => $this->configuration['search_box']['title'],
      '#description'    => $this->t('Enter the text that will be displayed when hovering the input field (HTML <em>title</em> attritube).'),
    );
    $form['search_box']['size'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Size'),
      '#size'           => 3,
      '#default_value'  => $this->configuration['search_box']['size'],
      '#description'    => $this->t('The default value is "@default".', array('@default' => 15)),
    );
    $form['search_box']['max_length'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Maximum length'),
      '#size'           => 3,
      '#default_value'  => $this->configuration['search_box']['max_length'],
      '#description'    => $this->t('The default value is "@default".', array('@default' => 128)),
      '#required'       => TRUE,
    );
  
    // Submit button.
    $form['submit'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Submit button'),
      '#open'         => TRUE,
    );
    $form['submit']['text'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Text'),
      '#default_value'  => $this->configuration['submit']['text'],
      '#description'    => $this->t('Enter the text for the submit button. Leave blank to hide it. The default value is "Search".'),
    );
    if ($this->moduleHandler->moduleExists('file')) {
      $form['submit']['image_path'] = array(
        '#type'           => 'textfield',
        '#title'          => $this->t('Image path'),
        '#description'    => $this->t('The path to the file you would like to use as submit button instead of the default text button.'),
        '#default_value'  => $this->configuration['submit']['image_path'],
      );
      $friendly_path = NULL;
      $default_image = 'search.png';
      if (\Drupal::service('file_system')->uriScheme($this->configuration['submit']['image_path']) == 'public') {
        $friendly_path = file_uri_target($this->configuration['submit']['image_path']);
      }
      if ($this->configuration['submit']['image_path'] && isset($friendly_path)) {
        $local_file = strtr($this->configuration['submit']['image_path'], array('public:/' => PublicStream::basePath()));
      }
      else {
        $local_file = \Drupal::theme()->getActiveTheme()->getPath() . '/' . $default_image;
      }

      $form['submit']['image_path']['#description'] = t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', array(
        '@implicit-public-file' => isset($friendly_path) ? $friendly_path : $default_image,
        '@explicit-file' => \Drupal::service('file_system')->uriScheme($this->configuration['submit']['image_path']) !== FALSE ? $this->configuration['submit']['image_path'] : 'public://' . $default_image,
        '@local-file' => $local_file,
      ));
      $form['submit']['image'] = array(
        '#type'           => 'file',
        '#title'          => $this->t('Image'),
        '#description'    => $this->t("If you don't have direct file access to the server, use this field to upload your image."),
      );
    }
    
    // Content.
    $form['content'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Content'),
      '#description'  => $this->t("Select the search types to present as search options in the search block. If none is selected, no selector will be displayed. <strong>Note</strong>: if there's only one type checked, the selector won't be displayed BUT only this type will be searched."),
      '#open'         => (count(array_filter($this->configuration['content']['types'])) + count(array_filter($this->configuration['content']['excluded']))),
    );
    $search_pages = \Drupal::entityTypeManager()->getStorage('search_page')->loadMultiple();
    $pages_options = array();
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() == 'node_search') {
        $pages_options[$page->id()] = $page->label();
      }
    }
    if (count($pages_options)) {
      $form['content']['page'] = array(
        '#type'           => 'select',
        '#title'          => $this->t('Search page'),
        '#description'    => $this->t('Select which page to use when searching content with this block. Pages are defined <a href=":link">here</a>.', array(':link' => Url::fromRoute('entity.search_page.collection', array(), array('fragment' => 'edit-search-pages'))->toString())),
        '#default_value'  => $this->configuration['content']['page'],
        '#options'        => $pages_options,
      );
    }
    $form['content']['types'] = array(
      '#type'           => 'checkboxes',
      '#title'          => $this->t('Content types'),
      '#default_value'  => $this->configuration['content']['types'],
      '#options'        => node_type_get_names(),
    );
    $other_pages_options = array();
    foreach ($search_pages as $page) {
      if ($page->getPlugin()->getPluginId() != 'node_search') {
        $other_pages_options[$page->id()] = $page->label();
      }
    }
    if (count($other_pages_options)) {
      $form['content']['other'] = array(
        '#type'           => 'checkboxes',
        '#title'          => $this->t('Other search pages'),
        '#default_value'  => $this->configuration['content']['other'],
        '#options'        => $other_pages_options,
      );
    }
    $form['content']['selector']['type'] = array(
      '#type'           => 'select',
      '#title'          => $this->t('Selector type'),
      '#options'        => array(
        'select'          => $this->t('Drop-down list'),
        'selectmultiple'  => $this->t('Drop-down list with multiple choices'),
        'radios'          => $this->t('Radio buttons'),
        'checkboxes'      => $this->t('Checkboxes'),
      ),
      '#description'    =>$this->t('Choose which selector type to use. Note: content types and other searches cannot be combined in a single search.'),
      '#default_value'  => $this->configuration['content']['selector']['type'],
    );
    $form['content']['selector']['label_visibility'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display label'),
      '#default_value'  => $this->configuration['content']['selector']['label_visibility'],
    );
    $form['content']['selector']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label text'),
      '#default_value'  => $this->configuration['content']['selector']['label'],
      '#description'    => $this->t('Enter the label text for the selector. The default value is "Search for".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[content][selector][label_visibility]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['content']['any'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('- Any -'),
    );
    $form['content']['any']['text'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('- Any content type - text'),
      '#default_value'  => $this->configuration['content']['any']['text'],
      '#required'       => TRUE,
      '#description'    => $this->t('Enter the text for "any content type" choice. The default value is "- Any -".'),
    );
    $form['content']['any']['restricts'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Choosing - Any - restricts the search to the selected content types.'),
      '#default_value'  => $this->configuration['content']['any']['restricts'],
      '#description'    => $this->t('If not checked, choosing - Any - will search in all content types.'),
    );
    $form['content']['any']['force'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Force - Any - to be displayed.'),
      '#default_value'  => $this->configuration['content']['any']['force'],
      '#description'    => $this->t('When only one content type is selected, the default behaviour is to hide the selector. If you need the - Any - option to be displayed, check this.'),
    );
    $form['content']['excluded'] = array(
      '#type'           => 'checkboxes',
      '#title'          => $this->t('Content exclusion'),
      '#description'    => $this->t("Select the content types you don't want to be displayed as results."),
      '#default_value'  => $this->configuration['content']['excluded'],
      '#options'        => node_type_get_names(),
    );

    // Taxonomy.
    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    if (count($vocabularies)) {
      $open = FALSE;
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        if ($this->configuration['taxonomy'][$vocId]['type'] != 'disabled') {
          $open = TRUE;
          break;
        }
      }
      $form['taxonomy'] = array(
        '#type'         => 'details',
        '#title'        => $this->t('Taxonomy'),
        '#description'  => $this->t('Select the vocabularies to present as search options in the search block. If none is selected, no selector will be displayed.'),
        '#open'         => $open,
      );
      // Get vocabularies forms.
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $form['taxonomy'][$vocId] = array(
          '#type'         => 'details',
          '#title'        => $voc->label(),
          '#open'         => $this->configuration['taxonomy'][$vocId]['type'] != 'disabled',
        );
        $form['taxonomy'][$vocId]['type'] = array(
          '#type'           => 'select',
          '#title'          => $this->t('Selector type'),
          '#options'        => array(
            'disabled'        => $this->t('Disabled'),
            'select'          => $this->t('Drop-down list'),
            'selectmultiple'  => $this->t('Drop-down list with multiple choices'),
            'radios'          => $this->t('Radio buttons'),
            'checkboxes'      => $this->t('Checkboxes'),
          ),
          '#description'    => $this->t('Choose which selector type to use.'),
          '#default_value'  => $this->configuration['taxonomy'][$vocId]['type'],
        );
        $form['taxonomy'][$vocId]['depth'] = array(
          '#type'           => 'textfield',
          '#title'          => $this->t('Depth'),
          '#size'           => 2,
          '#default_value'  => $this->configuration['taxonomy'][$vocId]['depth'],
          '#description'    => $this->t('Define the maximum depth of terms being displayed. The default value is "0" which disables the limit.'),
        );
        $form['taxonomy'][$vocId]['label_visibility'] = array(
          '#type'           => 'checkbox',
          '#title'          => $this->t('Display label'),
          '#default_value'  => $this->configuration['taxonomy'][$vocId]['label_visibility'],
        );
        $form['taxonomy'][$vocId]['label'] = array(
          '#type'           => 'textfield',
          '#title'          => $this->t('Label text'),
          '#default_value'  => $this->configuration['taxonomy'][$vocId]['label'],
          '#description'    => $this->t('Enter the label text for the selector. The default value is "@default".', array('@default' => $voc->label())),
          '#states' => array(
            'visible' => array(
              ':input[name="settings[taxonomy][' . $vocId . '][label_visibility]"]' => array('checked' => TRUE),
            ),
          ),
        );
        $form['taxonomy'][$vocId]['all_text'] = array(
          '#type'           => 'textfield',
          '#title'          => $this->t('-Any- text'),
          '#default_value'  => $this->configuration['taxonomy'][$vocId]['all_text'],
          '#required'       => TRUE,
          '#description'    => $this->t('Enter the text for "any term" choice. The default value is "- Any -".'),
        );
      }
    }
  
    // Criteria.
    $form['criteria'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Advanced search criteria'),
      '#open'         => $this->configuration['criteria']['or']['display'] || $this->configuration['criteria']['phrase']['display'] || $this->configuration['criteria']['negative']['display'],
    );
    $form['criteria']['or'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Or'),
      '#open'         => $this->configuration['criteria']['or']['display'],
    );
    $form['criteria']['or']['display'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display'),
      '#default_value'  => $this->configuration['criteria']['or']['display'],
    );
    $form['criteria']['or']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label'),
      '#default_value'  => $this->configuration['criteria']['or']['label'],
      '#description'    => $this->t('Enter the label text for this field. The default value is "Containing any of the words".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[criteria][or][display]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['criteria']['phrase'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Phrase'),
      '#open'         => $this->configuration['criteria']['phrase']['display'],
    );
    $form['criteria']['phrase']['display'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display'),
      '#default_value'  => $this->configuration['criteria']['phrase']['display'],
    );
    $form['criteria']['phrase']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label'),
      '#default_value'  => $this->configuration['criteria']['phrase']['label'],
      '#description'    => $this->t('Enter the label text for this field. The default value is "Containing the phrase".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[criteria][phrase][display]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['criteria']['negative'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Negative'),
      '#open'         => $this->configuration['criteria']['negative']['display'],
    );
    $form['criteria']['negative']['display'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display'),
      '#default_value'  => $this->configuration['criteria']['negative']['display'],
    );
    $form['criteria']['negative']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label'),
      '#default_value'  => $this->configuration['criteria']['negative']['label'],
      '#description'    => $this->t('Enter the label text for this field. The default value is "Containing none of the words".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[criteria][negative][display]"]' => array('checked' => TRUE),
        ),
      ),
    );
  
    // Search API support.
    if ($this->moduleHandler->moduleExists('search_api_page')) {
      $search_api_pages = search_api_page_load_multiple();
      $options[0] = t('None');
      foreach ($search_api_pages as $page) {
        $options[$page->id()] = $page->label();
      }
      $form['searchapi'] = array(
        '#type'         => 'details',
        '#title'        => $this->t('Search API'),
        '#collapsible'  => TRUE,
        '#collapsed'    => TRUE,
      );
      $form['searchapi']['page'] = array(
        '#type'           => 'select',
        '#title'          => $this->t('Search API Page to use'),
        '#options'        => $options,
        '#default_value'  => $this->configuration['searchapi']['page'],
      );
    }

    // Languages.
    $form['languages'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Languages'),
      '#description'  => $this->t("Select the languages to present as search options in the search block. If none is selected, no selector will be displayed. <strong>Note</strong>: if there's only one language checked, the selector won't be displayed BUT only this language will be searched."),
      '#open'         => count(array_filter($this->configuration['languages']['languages'])),
    );
    $languages = \Drupal::languageManager()->getLanguages();
    $languages_options = array(
      'current' => $this->t('- Current language -'),
    );
    foreach ($languages as $id => $language) {
      $languages_options[$id] = $language->getName();
    }
    $languages_options[Language::LANGCODE_NOT_SPECIFIED] = $this->t('- Not specified -');
    $languages_options[Language::LANGCODE_NOT_APPLICABLE] = $this->t('- Not applicable -');
    $form['languages']['languages'] = array(
      '#type'           => 'checkboxes',
      '#title'          => $this->t('Languages'),
      '#description'    => $this->t('Note: if <em>- Current language -</em> is selected, this current language won\'t be displayed twice.'),
      '#default_value'  => $this->configuration['languages']['languages'],
      '#options'        => $languages_options,
    );
    $form['languages']['selector']['type'] = array(
      '#type'           => 'select',
      '#title'          => $this->t('Selector type'),
      '#options'        => array(
        'select'          => $this->t('Drop-down list'),
        'selectmultiple'  => $this->t('Drop-down list with multiple choices'),
        'radios'          => $this->t('Radio buttons'),
        'checkboxes'      => $this->t('Checkboxes'),
      ),
      '#description'    => $this->t('Choose which selector type to use.'),
      '#default_value'  => $this->configuration['languages']['selector']['type'],
    );
    $form['languages']['selector']['label_visibility'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display label'),
      '#default_value'  => $this->configuration['languages']['selector']['label_visibility'],
    );
    $form['languages']['selector']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label text'),
      '#default_value'  => $this->configuration['languages']['selector']['label'],
      '#description'    => $this->t('Enter the label text for the selector. The default value is "Languages".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[languages][selector][label_visibility]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['languages']['any'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('- Any -'),
    );
    $form['languages']['any']['text'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('- Any language - text'),
      '#default_value'  => $this->configuration['languages']['any']['text'],
      '#required'       => TRUE,
      '#description'    => $this->t('Enter the text for "any language" choice. The default value is "- Any -".'),
    );
    $form['languages']['any']['restricts'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Choosing - Any - restricts the search to the selected languages.'),
      '#default_value'  => $this->configuration['languages']['any']['restricts'],
      '#description'    => $this->t('If not checked, choosing - Any - will search in all languages.'),
    );
    $form['languages']['any']['force'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Force - Any - to be displayed.'),
      '#default_value'  => $this->configuration['languages']['any']['force'],
      '#description'    => $this->t('When only one language is selected, the default behaviour is to hide the selector. If you need the - Any - option to be displayed, check this.'),
    );

    // Custom Paths.
    $form['paths'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Custom search paths'),
      '#open'         => $this->configuration['paths']['list'] != '',
    );
    $form['paths']['selector']['type'] = array(
      '#type'           => 'select',
      '#title'          => $this->t('Selector type'),
      '#options'        => array(
        'select'          => $this->t('Drop-down list'),
        'radios'          => $this->t('Radio buttons'),
      ),
      '#description'    => $this->t('Choose which selector type to use.'),
      '#default_value'  => $this->configuration['paths']['selector']['type'],
    );
    $form['paths']['selector']['label_visibility'] = array(
      '#type'           => 'checkbox',
      '#title'          => $this->t('Display label'),
      '#default_value'  => $this->configuration['paths']['selector']['label_visibility'],
    );
    $form['paths']['selector']['label'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Label text'),
      '#default_value'  => $this->configuration['paths']['selector']['label'],
      '#description'    => $this->t('Enter the label text for the selector. The default value is "Customize your search".'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[paths][selector][label_visibility]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['paths']['list'] = array(
      '#type'           => 'textarea',
      '#title'          => $this->t('Paths'),
      '#default_value'  => $this->configuration['paths']['list'],
      '#rows'           => 3,
      '#description'    => $this->t('If you want to use custom search paths, enter them here in the form <em>path</em>|<em>label</em>, one per line (if only one path is specified, the selector will be hidden). The [key] token will be replaced by what is entered in the search box, the [types] token will be replaced by the selected content types machine name(s) and the [terms] token will be replaced by the selected taxonomy term id(s). Ie: mysearch/[key]|My custom search label. The [current_path] token can also be used to use the current URL path of the page being viewed.'),
    );
    $form['paths']['separator'] = array(
      '#type'           => 'textfield',
      '#title'          => $this->t('Arguments separator'),
      '#description'    => $this->t('Enter a separator that will be used when multiple content types or taxonomy terms are selected and [types] and/or [terms] tokens are used.'),
      '#default_value'  => $this->configuration['paths']['separator'],
      '#size'           => 2,
    );
    
    // Ordering.
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'custom_search/custom_search.ordering';

    $form['order'] = array(
      '#type'         => 'details',
      '#title'        => $this->t('Elements layout'),
      '#description'  => $this->t('Order the form elements as you want them to be displayed. If you put elements in the Popup region, they will only appear when the search field is clicked.'),
      '#open'         => TRUE,
    );
    $form['order']['table'] = array(
      '#type'       => 'table',
      '#header'     => array($this->t('Element'), $this->t('Region'), $this->t('Weight')),
      '#attributes' => array(
        'id' => 'elements',
      ),
    );

    $elements = array(
      'search_box'  => array(
        'label'   => $this->t('Search box'),
        'config'  => $this->configuration['search_box'],
      ),
      'submit'      => array(
        'label'   => $this->t('Submit button'),
        'config'  => $this->configuration['submit'],
      ),
      'content'     => array(
        'label'   => $this->t('Content types'),
        'config'  => $this->configuration['content'],
      ),
      'or'          => array(
        'label'   => $this->t('Criteria: Containing any of the words'),
        'config'  => $this->configuration['criteria']['or'],
      ),
      'phrase'      => array(
        'label'   => $this->t('Criteria: Containing the phrase'),
        'config'  => $this->configuration['criteria']['phrase'],
      ),
      'negative'    => array(
        'label'   => $this->t('Criteria: Containing none of the words'),
        'config'  => $this->configuration['criteria']['negative'],
      ),
      'languages'   => array(
        'label'   => $this->t('Languages'),
        'config'  => $this->configuration['languages'],
      ),
      'paths'       => array(
        'label'   => $this->t('Custom Path'),
        'config'  => $this->configuration['paths'],
      ),
    );
    if (count($vocabularies)) {
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $elements['voc-' . $vocId] = array(
          'label'   => $this->t('Taxonomy: @name', array('@name' => $voc->label())),
          'config'  => $this->configuration['taxonomy'][$vocId],
        );
      }
    }
    uasort($elements, array($this, 'weightsSort'));
    $regions = array(
      'block' => $this->t('Block'),
      'popup' => $this->t('Popup'),
    );

    foreach ($elements as $id => $element) {
      $element_config = $element['config'];
      $regionsElements[$element_config['region']][$id] = $element;
    }

    foreach ($regions as $region => $title) {
      $form['order']['table']['#tabledrag'][] = array(
        'action'        => 'match',
        'relationship'  => 'sibling',
        'group'         => 'order-region',
        'subgroup'      => 'order-region-' . $region,
        'hidden'        => FALSE,
      );
      $form['order']['table']['#tabledrag'][] = array(
        'action'        => 'order',
        'relationship'  => 'sibling',
        'group'         => 'order-weight',
        'subgroup'      => 'order-weight-' . $region,
      );
      $form['order']['table'][$region] = array(
        '#attributes'   => array(
          'class'       => array('region-title', 'region-title-' . $region),
          'no_striping' => TRUE,
        ),
      );
      $form['order']['table'][$region]['title'] = array(
        '#markup'             => $title,
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      $form['order']['table'][$region . '-message'] = array(
        '#attributes' => array(
          'class' => array(
            'region-message',
            'region-' . $region . '-message',
            empty($regionsElements[$region]) ? 'region-empty' : 'region-populated',
          ),
        ),
      );
      $form['order']['table'][$region . '-message']['message'] = array(
        '#markup'             => '<em>' . $this->t('No elements in this region') . '</em>',
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      if (isset($regionsElements[$region])) {
        foreach ($regionsElements[$region] as $id => $element) {
          $element_config = $element['config'];
          $form['order']['table'][$id]['#attributes']['class'][] = 'draggable';
          $form['order']['table'][$id]['#weight'] = $element_config['weight'];
          $form['order']['table'][$id]['element'] = array('#markup' => $element['label']);
          $form['order']['table'][$id]['region'] = array(
            '#type'           => 'select',
            '#title'          => $this->t('Region for @title', array('@title' => $element['label'])),
            '#title_display'  => 'invisible',
            '#options'        => array(
              'block' => $this->t('Block'),
              'popup' => $this->t('Popup'),
            ),
            '#default_value'  => $region,
            '#attributes'     => array('class' => array('order-region', 'order-region-' . $region)),
          );
          $form['order']['table'][$id]['weight'] = array(
            '#type'           => 'weight',
            '#title'          => $this->t('Weight for @title', array('@title' => $element['label'])),
            '#title_display'  => 'invisible',
            '#default_value'  => $element_config['weight'],
            '#attributes'     => array('class' => array('order-weight', 'order-weight-' . $element_config['region'])),
          );
        }
      }

    }

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockValidate().
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue(array('paths', 'list')) != '') {
      $lines = explode("\n", $form_state->getValue(array('paths', 'list')));
      foreach ($lines as $line) {
        if (strpos($line, '|') < 1) {
          $form_state->setErrorByName('list', $this->t('Custom path must be in the form <em>path</em>|<em>label</em>.'));
          break;
        }
      }
    }
    if ($this->moduleHandler->moduleExists('file')) {
      // Handle file uploads.
      $validators = array('file_validate_is_image' => array());

      // Check for a new uploaded logo.
      $file = file_save_upload('settings', $validators, FALSE, 0);
      if (isset($file)) {
        // File upload was attempted.
        if ($file) {
          $directory_path = 'public://custom_search';
          file_prepare_directory($directory_path, FILE_CREATE_DIRECTORY);
          $filename = file_unmanaged_copy($file->getFileUri(), $directory_path);
          $form_state->setValue(array('submit', 'image_path'), $filename);
        }
        else {
          // File upload failed.
          $form_state->setErrorByName('image', $this->t('The submit image could not be uploaded.'));
        }
      }
      // If the user provided a path for a logo or favicon file, make sure a file
      // exists at that path.
      if (!$form_state->isValueEmpty(array('submit', 'image_path'))) {
        $path = $this->validatePath($form_state->getValue(array('submit', 'image_path')));
        if (!$path) {
          $form_state->setErrorByName('image_path', $this->t('The submit image path is invalid.'));
        }
      }
    }

  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['search_box'] = array(
      'label_visibility'  => $form_state->getValue(array('search_box', 'label_visibility')),
      'label'             => $form_state->getValue(array('search_box', 'label')),
      'placeholder'       => $form_state->getValue(array('search_box', 'placeholder')),
      'title'             => $form_state->getValue(array('search_box', 'title')),
      'size'              => $form_state->getValue(array('search_box', 'size')),
      'max_length'        => $form_state->getValue(array('search_box', 'max_length')),
      'weight'            => $form_state->getValue(array('order', 'table', 'search_box', 'weight')),
      'region'            => $form_state->getValue(array('order', 'table', 'search_box', 'region')),
    );

    $this->configuration['submit'] = array(
      'text'        => $form_state->getValue(array('submit', 'text')),
      'weight'      => $form_state->getValue(array('order', 'table', 'submit', 'weight')),
      'region'      => $form_state->getValue(array('order', 'table', 'submit', 'region')),
    );
    // If the user uploaded a new submit image, save it to a permanent location.
    if ($this->moduleHandler->moduleExists('file')) {
      // If the user entered a path relative to the system files directory for the submit image,
      // store a public:// URI so the theme system can handle it.
      if (!$form_state->isValueEmpty(array('submit', 'image_path'))) {
        $this->configuration['submit']['image_path'] = $this->validatePath($form_state->getValue(array('submit', 'image_path')));
      } 
    }

    $this->configuration['content'] = array(
      'page'      => $form_state->getValue(array('content', 'page')),
      'types'     => $form_state->getValue(array('content', 'types')),
      'other'     => $form_state->getValue(array('content', 'other')),
      'selector'  => array(
        'type'             => $form_state->getValue(array('content', 'selector', 'type')),
        'label_visibility' => $form_state->getValue(array('content', 'selector', 'label_visibility')),
        'label'            => $form_state->getValue(array('content', 'selector', 'label')),
      ),
      'any'       => array(
        'text'      => $form_state->getValue(array('content', 'any', 'text')),
        'restricts' => $form_state->getValue(array('content', 'any', 'restricts')),
        'force'     => $form_state->getValue(array('content', 'any', 'force')),
      ),
      'excluded'  => $form_state->getValue(array('content', 'excluded')),
      'weight'    => $form_state->getValue(array('order', 'table', 'content', 'weight')),
      'region'    => $form_state->getValue(array('order', 'table', 'content', 'region')),
    );

    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    if (count($vocabularies)) {
      foreach ($vocabularies as $voc) {
        $vocId = $voc->id();
        $this->configuration['taxonomy'][$vocId] = array(
          'type'              => $form_state->getValue(array('taxonomy', $vocId, 'type')),
          'depth'             => $form_state->getValue(array('taxonomy', $vocId, 'depth')),
          'label_visibility'  => $form_state->getValue(array('taxonomy', $vocId, 'label_visibility')),
          'label'             => $form_state->getValue(array('taxonomy', $vocId, 'label')),
          'all_text'          => $form_state->getValue(array('taxonomy', $vocId, 'all_text')),
          'weight'            => $form_state->getValue(array('order', 'table', 'voc-' . $vocId, 'weight')),
          'region'            => $form_state->getValue(array('order', 'table', 'voc-' . $vocId, 'region')),
        );
      }
    }

    $this->configuration['criteria'] = array(
      'or'        => array(
        'display' => $form_state->getValue(array('criteria', 'or', 'display')),
        'label'   => $form_state->getValue(array('criteria', 'or', 'label')),
        'weight'  => $form_state->getValue(array('order', 'table', 'or', 'weight')),
        'region'  => $form_state->getValue(array('order', 'table', 'or', 'region')),
      ),
      'phrase'    => array(
        'display' => $form_state->getValue(array('criteria', 'phrase', 'display')),
        'label'   => $form_state->getValue(array('criteria', 'phrase', 'label')),
        'weight'  => $form_state->getValue(array('order', 'table', 'phrase', 'weight')),
        'region'  => $form_state->getValue(array('order', 'table', 'phrase', 'region')),
      ),
      'negative'  => array(
        'display' => $form_state->getValue(array('criteria', 'negative', 'display')),
        'label'   => $form_state->getValue(array('criteria', 'negative', 'label')),
        'weight'  => $form_state->getValue(array('order', 'table', 'negative', 'weight')),
        'region'  => $form_state->getValue(array('order', 'table', 'negative', 'region')),
      ),
    );

    if ($this->moduleHandler->moduleExists('search_api_page')) {
      $this->configuration['searchapi']['page'] = $form_state->getValue(array('searchapi', 'page'));
    }

    $this->configuration['languages'] = array(
      'languages' => $form_state->getValue(array('languages', 'languages')),
      'selector'  => array(
        'type'             => $form_state->getValue(array('languages', 'selector', 'type')),
        'label_visibility' => $form_state->getValue(array('languages', 'selector', 'label_visibility')),
        'label'            => $form_state->getValue(array('languages', 'selector', 'label')),
      ),
      'any'       => array(
        'text'      => $form_state->getValue(array('languages', 'any', 'text')),
        'restricts' => $form_state->getValue(array('languages', 'any', 'restricts')),
        'force'     => $form_state->getValue(array('languages', 'any', 'force')),
      ),
      'weight'    => $form_state->getValue(array('order', 'table', 'languages', 'weight')),
      'region'    => $form_state->getValue(array('order', 'table', 'languages', 'region')),
    );

    $this->configuration['paths'] = array(
      'list'            => $form_state->getValue(array('paths', 'list')),
      'selector'        => array(
        'type'              => $form_state->getValue(array('paths', 'selector', 'type')),
        'label_visibility'  => $form_state->getValue(array('paths', 'selector', 'label_visibility')),
        'label'             => $form_state->getValue(array('paths', 'selector', 'label')),
      ),
      'separator'       => $form_state->getValue(array('paths', 'separator')),
      'weight'          => $form_state->getValue(array('order', 'table', 'paths', 'weight')),
      'region'          => $form_state->getValue(array('order', 'table', 'paths', 'region')),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\custom_search\Form\CustomSearchBlockForm', $this->configuration);
  }

  /**
   * Helper function for the form.
   *
   * Attempts to validate normal system paths, paths relative to the public files
   * directory, or stream wrapper URIs. If the given path is any of the above,
   * returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    // Absolute local file paths are invalid.
    if (\Drupal::service('file_system')->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (\Drupal::service('file_system')->uriScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }
  
  /**
   * Helper function for sorting elements in the ordering table.
   *
   * @param mixed $a
   *   The first value to compare.
   * @param mixed $b
   *   The second value to compare.
   * @return int
   *   An integer less than, equal to, or greater than zero if the first argument
   *   is considered to be respectively less than, equal to, or greater than the second.
   */
  private static function weightsSort($a, $b) {
    $config_a = $a['config'];
    $config_b = $b['config'];
    if ($config_a['weight'] == $config_b['weight']) {
        return 0;
    }
    return ($config_a['weight'] < $config_b['weight']) ? -1 : 1;
  }

}
