<?php

/**
 * @file
 * Contains \Drupal\skinr\Tests\SkinrApiTest.
 */

namespace Drupal\skinr\Tests;

use Drupal\Component\Uuid\Uuid;
use Drupal\skinr\Entity\Skin;


/**
 * Tests Skinr API functionality.
 *
 * @group skinr
 */
class SkinrApiTest extends SkinrWebTestBase {

  protected $profile = 'testing';

  protected $user;

  public static $modules = array('skinr', 'skinr_test', 'skinr_test_incompatible');

  public function setUp() {
    parent::setUp();
    // Enable skinr_test_subtheme, but NOT the basetheme.
    \Drupal::service('theme_handler')->install(array('skinr_test_subtheme'));
  }

  /**
   * Tests skinr_implements().
   */
  public function testSkinrImplementsAPI() {
    // Verify that skinr_implements() only returns extensions that are
    // compatible with this version of Skinr.
    $extensions = skinr_implements_api();
    $this->verbose(highlight_string('<?php ' . var_export($extensions, TRUE), TRUE));

    // The expected extensions and their specific properties, if any.
    $all_expected = array(
      // Skinr is always expected.
      'skinr' => array(
        'include file' => drupal_get_path('module', 'skinr') . '/skinr.skinr.inc',
      ),
      // skinr_test has been installed.
      'skinr_test' => array(
        'directory' => 'skins',
        'include file' => drupal_get_path('module', 'skinr_test') . '/skinr_test.skinr.inc',
      ),
      // System and node are required core modules, so always expected.
      'system' => array (
        'version' => \Drupal::VERSION,
        'path' => drupal_get_path('module', 'skinr') . '/modules',
        'include file' => drupal_get_path('module', 'skinr') . '/modules/system.skinr.inc',
      ),
      'skinr_test_basetheme' => array(
        'type' => 'theme',
        'api' => SKINR_VERSION,
        'directory' => 'skins',
        'base themes' => array(),
        'sub themes' => array_combine(array('skinr_test_subtheme'), array('skinr_test_subtheme')),
        'include file' => drupal_get_path('theme', 'skinr_test_basetheme') . '/skinr_test_basetheme.skinr.inc',
      ),
      'skinr_test_subtheme' => array(
        'type' => 'theme',
        'api' => SKINR_VERSION,
        'directory' => 'skins',
        'base themes' => array_combine(array('skinr_test_basetheme'), array('skinr_test_basetheme')),
        'sub themes' => array(),
        'include file' => drupal_get_path('theme', 'skinr_test_subtheme') . '/skinr_test_subtheme.skinr.inc',
      ),
    );
    // When running tests on Skinr code packaged by drupal.org, all 'version'
    // properties will have the version of the Skinr module. When running on a
    // repository checkout, the version is NULL (undefined).
    $skinr_module_info = system_get_info('module', 'skinr');
    $skinr_module_version = (!empty($skinr_module_info['version']) ? $skinr_module_info['version'] : NULL);
    foreach ($all_expected as $name => $expected) {
      // Populate defaults.
      $expected += array(
        'type' => 'module',
        'name' => $name,
        'version' => $skinr_module_version,
      );
      $expected += array(
        'path' => drupal_get_path($expected['type'], $name),
        'directory' => NULL,
      );
      $this->assertEqual($extensions[$name], $expected, t('%extension implementation found:<pre>@data</pre>', array(
        '%extension' => $name,
        '@data' => var_export($extensions[$name], TRUE),
      )));
      unset($extensions[$name]);
    }
    // Ensure that skinr_test_incompatible is not contained.
    $this->assertTrue(!isset($extensions['skinr_test_incompatible']), 'Incompatible extension not found.');
    // After asserting all expected, the list of extensions should be empty.
    $this->assertTrue(empty($extensions), 'No unexpected extensions found.');
  }

  /**
   * Test module_implements().
   */
  function testSkinrImplements() {
    // Test clearing cache.
    \Drupal::cache('bootstrap')->invalidate('skinr_implements');
    $this->assertFalse(\Drupal::cache('bootstrap')->get('skinr_implements'), t('The skinr implements cache is empty.'));
    $this->drupalGet('');
    $this->assertTrue(\Drupal::cache('bootstrap')->get('skinr_implements'), t('The skinr implements cache is populated after requesting a page.'));

    // Test clearing cache with an authenticated user.
    $this->user = $this->drupalCreateUser(array());
    $this->drupalLogin($this->user);
    \Drupal::cache('bootstrap')->invalidate('skinr_implements');
    $this->drupalGet('');
    $this->assertTrue(\Drupal::cache('bootstrap')->get('skinr_implements'), t('The skinr implements cache is populated after requesting a page.'));

    // Make sure $module.skinr.inc files (both in the module root, which are
    // auto-loaded by drupal, and in custom paths and themes, which are
    // loaded by skinr_implements()) are loaded when the hook is called. Also
    // ensure only module that implement the current Skinr API are loaded.
    $modules = skinr_implements('skinr_skin_info');

    // Ensure the hook is found in includes.
    $this->assertTrue(in_array('skinr_test', $modules), 'Hook found in $module.skinr.inc file auto-loaded by module_hook().');
    $this->assertTrue(in_array('skinr_test_subtheme', $modules), 'Hook found in $module.skinr.inc file in custom path.');

    // Ensure that skinr_test_incompatible is not included.
    $this->assertTrue(!in_array('skinr_test_incompatible', $modules), 'Hook in incompatible module not found.');
  }

  /**
   * Tests skinr_implements() caching and auto-loading.
   */
  function testSkinrImplementsCache() {
    \Drupal::service('module_installer')->install(array('block'));
    $this->resetAll();
    // Enable main system block for content region and the user menu block for
    // the first sidebar.
    $default_theme = \Drupal::config('system.theme')->get('default');

    $this->drupalPlaceBlock('system_main_block', array('region' => 'content'));
    $this->drupalPlaceBlock('system_powered_by_block', array('region' => 'sidebar_first'));

    // Enable a skin defined in an include file, which applies to a module
    // element that is equally registered in an include file (built-in Block
    // module integration).
    $skin = Skin::create(array(
      'theme' => $default_theme,
      'element_type' => 'block',
      'element' => 'bartik_powered',
      'skin' => 'skinr_test_font',
      'options' => array('font_1' => 'font_1'),
      'status' => 1,
    ));
    $skin->save();

    // Verify the skin is contained in the output.
    $this->drupalGet('');
    $this->assertSkinrClass('block-system-powered-by-block', 'font-1', 'Skin found.');

    // Once again, so we hit the cache.
    $this->drupalGet('');
    $this->assertSkinrClass('block-system-powered-by-block', 'font-1', 'Skin found.');

    // Visit skin edit page after to test for groups, after hitting cache.
    $this->drupalGet('skinr-test/hook-dynamic-loading');
    $this->assertText('success!', t('$module.skinr.inc file auto-loaded.'));
  }

  /**
   * Test that module_invoke_all() can load a hook defined in hook_hook_info().
   */
  function testSkinrInvokeAll() {
    // Ensure functions from $module.skinr.inc in both module root and in
    // custom paths are triggered.
    $config_info = skinr_invoke_all('skinr_config_info');
    $this->verbose(highlight_string('<?php ' . var_export($config_info, TRUE), TRUE));
    $this->assertTrue(in_array('system', $config_info), 'Function triggered in $module.skinr.inc file auto-loaded by module_hook().');
    $this->assertTrue(in_array('node', $config_info), 'Function triggered in $module.skinr.inc file in custom path.');

    // Ensure that skinr_test_incompatible is not included.
    $this->assertTrue(!in_array('skinr_test_incompatible', $config_info), 'Function in incompatible module not triggered.');
  }

  /**
   * Tests hook_skinr_skin_info().
   */
  public function testSkinrSkinInfo() {
    // Verify that skinr_get_skin_info() finds and returns all registered skins
    // in $module.skinr.inc files as well as Skinr plugin files, but does not
    // return skins that are incompatible with the current Skinr API version.
    $skin_info = skinr_get_skin_info();

    $path = drupal_get_path('module', 'skinr_test');

    // skinr_test_font is registered via hook_skinr_skin_info() in
    // skinr_test.skinr.inc.
    $this->assertTrue(isset($skin_info['skinr_test_font']), 'Skin registered in $module.skinr.inc found.');
    $this->assertEqual($skin_info['skinr_test_font']['source']['path'], $path, t('Skin path points to module directory: @path', array(
      '@path' => $skin_info['skinr_test_font']['source']['path'],
    )));
    unset($skin_info['skinr_test_font']);

    // Test that an invalid class is not included.
    $this->assertTrue(isset($skin_info['skinr_test_invalid_class']), 'Skin with invalid class found.');
    $this->assertEqual($skin_info['skinr_test_invalid_class']['options']['invalid_class']['class'], array(), 'Invalid skin class is reset to array.');
    unset($skin_info['skinr_test_invalid_class']);

    // skinr_test_example is registered via hook_skinr_skin_PLUGIN_info() in
    // skins/example.inc.
    $this->assertTrue(isset($skin_info['skinr_test_example']), 'Skin registered in plugin file found.');
    $this->assertEqual($skin_info['skinr_test_example']['source']['path'], $path . '/skins/example', t('Skin path points to plugin directory: @path', array(
      '@path' => $skin_info['skinr_test_example']['source']['path'],
    )));
    unset($skin_info['skinr_test_example']);

    // skinr_test_basetheme is registered via hook_skinr_skin_info() in
    // skinr_test_basetheme.skinr.inc.
    $this->assertTrue(isset($skin_info['skinr_test_basetheme']), 'Skin registered in $basetheme.skinr.inc found.');
    $this->assertEqual($skin_info['skinr_test_basetheme']['source']['path'], $path . '/themes/skinr_test_basetheme', t('Skin path points to basetheme directory: @path', array(
      '@path' => $skin_info['skinr_test_basetheme']['source']['path'],
    )));
    $default_theme = \Drupal::config('system.theme')->get('default');
    $this->assertEqual($skin_info['skinr_test_basetheme']['status'][$default_theme], 0, 'Basetheme skin is disabled for default theme.');
    $this->assertEqual($skin_info['skinr_test_basetheme']['status']['skinr_test_basetheme'], 1, 'Basetheme skin is enabled for Skinr test basetheme.');
    unset($skin_info['skinr_test_basetheme']);

    // skinr_test_subtheme is registered via hook_skinr_skin_info() in
    // skinr_test_subtheme.skinr.inc.
    $this->assertTrue(isset($skin_info['skinr_test_subtheme']), 'Skin registered in $subtheme.skinr.inc found.');
    $this->assertEqual($skin_info['skinr_test_subtheme']['source']['path'], $path . '/themes/skinr_test_subtheme', t('Skin path points to subtheme directory: @path', array(
      '@path' => $skin_info['skinr_test_subtheme']['source']['path'],
    )));
    unset($skin_info['skinr_test_subtheme']);

    // Ensure that skinr_test_incompatible is not contained.
    $this->assertTrue(!isset($skin_info['skinr_test_incompatible']), 'Incompatible skin not found.');
    // After asserting all expected, the list of skins should be empty.
    $this->assertTrue(empty($skin_info), t('No unexpected skins found: <pre>@data</pre>', array(
      '@data' => var_export($skin_info, TRUE),
    )));
  }

  /**
   * Tests hook_skinr_group_info().
   */
  public function testSkinrGroupInfo() {
    $group_info = skinr_get_group_info();

    // Verify that default skin groups are found.
    $all_expected = array(
      'general' => array(
        'title' => t('General'),
        'weight' => -10,
      ),
      'box' => array(
        'title' => t('Box styles'),
      ),
      'typography' => array(
        'title' => t('Typography'),
      ),
      'layout' => array(
        'title' => t('Layout'),
      ),
    );
    foreach ($all_expected as $name => $expected) {
      // We don't want to be pixel-perfect here.
      if (isset($group_info[$name]['description'])) {
        $expected['description'] = $group_info[$name]['description'];
      }
      $expected += array(
        'description' => '',
        'weight' => 0,
      );
      $this->assertEqual($group_info[$name], $expected, t('Group %group found:<pre>@data</pre>', array(
        '%group' => $name,
        '@data' => var_export($group_info[$name], TRUE),
      )));
      unset($group_info[$name]);
    }
    // After asserting all expected, the list of extensions should be empty.
    $this->assertTrue(empty($group_info), 'No unexpected groups found.');
  }

  /**
   * Tests hook_skinr_config_info().
   */
  public function testSkinrConfigInfo() {
    // Verify that skinr_get_config_info() finds all existing and compatible
    // hook_skinr_config_info() implementations.
    $config = skinr_get_config_info();

    // Skinr's own implementation in skinr.skinr.inc should always be found.
    $this->assertTrue(isset($config['system']), 'hook_skinr_config_info() in $module.skinr.inc found.');
    unset($config['system']);

    // Skinr's implementation on behalf of Node module in modules/node.skinr.inc
    // should be found.
    $this->assertTrue(isset($config['node']), 'hook_skinr_config_info() in a custom path found.');
    unset($config['node']);

    // Ensure that skinr_test_incompatible is not included.
    $this->verbose(highlight_string('<?php ' . var_export($config, TRUE), TRUE));
    $this->assertTrue(!isset($config['skinr_test_incompatible']), 'Incompatible hook_skinr_config_info() not found.');
    // After asserting all expected, the list of skins should be empty.
    $this->assertTrue(empty($config), 'No unexpected skins found.');
  }

  /**
   * Test hook invocations for CRUD operations on skin configurations.
   */
  public function testSkinrSkinHooks() {
    $skin = Skin::create(array(
      'theme' => 'skinr_test_subtheme',
      'element_type' => 'block',
      'element' => 'system__user-menu',
      'skin' => 'skinr_test_subtheme',
      'options' => array('option1' => 'option1', 'option2' => 'option2'),
      'status' => 1,
    ));

    $_SESSION['skinr_test'] = array();
    $skin->save();

    $this->assertHookMessage('skinr_test_skinr_skin_presave called');
    $this->assertHookMessage('skinr_test_skinr_skin_insert called');

    $_SESSION['skinr_test'] = array();
    $skin = Skin::load($skin->id());

    $this->assertHookMessage('skinr_test_skinr_skin_load called');

    $_SESSION['skinr_test'] = array();
    /* @var Skin $skin */
    $skin = \Drupal::entityManager()->getStorage('skin')->loadUnchanged($skin->id());

    $this->assertHookMessage('skinr_test_entity_load called');

    $_SESSION['skinr_test'] = array();
    $skin->setOptions(array('option3' => 'option3'));
    $skin->save();

    $this->assertHookMessage('skinr_test_skinr_skin_presave called');
    $this->assertHookMessage('skinr_test_skinr_skin_update called');

    $_SESSION['skinr_test'] = array();
    $skin->delete();

    $this->assertHookMessage('skinr_test_entity_delete called');
  }

  /**
   * Test skinr_skin_save() against invalid entries.
   */
  public function _testSkinrSkinLoadSave() {
    // Only save valid skins.
    $skin = Skin::create(array(
      'theme' => '',
      'element_type' => 'block',
      'element' => 'system__user-menu',
      'skin' => 'skinr_test_subtheme',
      'options' => array('option1' => 'option1', 'option2' => 'option2'),
      'status' => 1,
    ));
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->theme is empty.');

    $skin->theme = 'skinr_test_subtheme';
    $skin->element_type = '';
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->element_type is empty.');

    $skin->element_type = 'block';
    $skin->element = '';
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->element is empty.');

    $skin->element = 'system-user-menu';
    $skin->skin = '';
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->skin is empty.');

    $skin->skin = 'skinr_test_subtheme';
    $skin->setOptions('');
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->options is not array.');

    $skin->setOptions(array());
    $this->assertFalse($skin->save(), 'Skin configuration object saved when $skin->options is empty array.');

    $skin->setOptions(array('option1' => 0, 'option2' => 0));
    $this->assertFalse($skin->save(), 'Skin configuration object not saved when $skin->options is complex empty array.');

    $skin->setOptions(array('option1' => 'option1', 'option2' => 'option2'));
    $this->assertTrue($skin->save(), 'Skin configuration object was saved.');
    $this->assertTrue($skin->id(), 'ID added to skin configuration object.');
    $this->assertTrue(Uuid::isValid($skin->id()), 'ID for skin configuration object is valid.');

    // Test loading a skin configuration.
    /* @var Skin $loaded_skin */
    $loaded_skin = Skin::load($skin->id());
    $this->assertTrue(is_array($loaded_skin->getOptions()), 'Options for skin configuration object are unserialized.');

    $this->assertTrue($loaded_skin->theme == $skin->theme && $loaded_skin->element_type == $skin->element_type && $loaded_skin->element == $skin->element && $loaded_skin->status() == $skin->status() && $loaded_skin->getOption(0) == $skin->getOption(0) && $loaded_skin->getOption(1) == $skin->getOption(1), 'Skin configuration object loaded.');

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($loaded_skin) == SKINR_STORAGE_IN_DATABASE, 'Storage indicator indicates stored in database.');

    // Save a second skin.
    $second_skin = Skin::create(array(
      'uuid' => \Drupal::service('uuid')->generate(),
      'theme' => 'skinr_test_subtheme',
      'element_type' => 'block',
      'element' => 'system__main',
      'skin' => 'skinr_test_subtheme',
      'options' => array('option3' => 'option3'),
      'status' => 1,
    ));
    skinr_skin_save($second_skin);

    // Test loading multiple skin configurations.
    $skins = Skin::loadMultiple(array($skin->id(), $second_skin->id()));
    $this->assertTrue(count($skins) == 2 && $skins[$skin->id()]->id() && $skins[$second_skin->id()]->id(), 'Successfully loaded multiple skins.');

    // Test loading all skin configurations.
    drupal_static_reset('skinr_skin_load_multiple');
    $skins = Skin::loadMultiple();
    $this->assertTrue(count($skins) == 2 && $skins[$skin->id()]->id() && $skins[$second_skin->id()]->id(), 'Successfully loaded all skins.');

    // Test $skin->uuid not overwritten when given.
    $this->assertTrue($skins[$second_skin->id()]->id() == $second_skin->id(), 'UUID for skin configuration not overwritten when manually set.');

    // Test skinr_skin_uuid_to_sid().
    $this->assertTrue(skinr_skin_uuid_to_sid($second_skin->id()) == $second_skin->id(), 'Successfully got SID based on UUID for skin configuration object.');

    // Test skinr_skin_sid_to_uuid().
    $this->assertTrue(skinr_skin_sid_to_uuid($second_skin->id()) == $second_skin->id(), 'Successfully got UUID based on SID for skin configuration object.');

    // Test skinr_skin_load_by_uuid().
    $loaded_skin = skinr_skin_load_by_uuid($second_skin->id());
    $this->assertTrue($loaded_skin->id() == $second_skin->id(), 'Skin configuration object loaded using UUID.');

    // Test skinr_skin_load_by_uuid() when bad UUID given.
    $this->assertFalse(skinr_skin_load_by_uuid(\Drupal::service('uuid')->generate()), 'Skin configuration object not loaded when using non-existing UUID.');
  }

  /**
   * Test default skin configurations (in code) with duplicates.
   */
  public function testSkinrSkinDefaultsDuplicates() {
    $uuid = '501ff0c3-db03-0944-9910-3a788f38097a';

    \Drupal::service('module_installer')->install(array('skinr_test_default'));
    $default_skins = _skinr_skin_get_defaults();
    $this->verbose(highlight_string('<?php ' . print_r($default_skins, TRUE), TRUE));

    // Clear caches.
    drupal_static_reset('_skinr_skin_get_defaults');

    \Drupal::service('module_installer')->install(array('skinr_test_default_duplicate'));
    $default_skins = _skinr_skin_get_defaults();
    $this->verbose(highlight_string('<?php ' . print_r($default_skins, TRUE), TRUE));

    $this->assertFalse(is_array($default_skins[$uuid]), 'Default skin configuration replaced its duplicate.');

    // Clean up.
    \Drupal::service('module_installer')->uninstall(array('skinr_test_default_duplicate'));
    drupal_static_reset('_skinr_skin_get_defaults');
  }

  /**
   * Test default skin configurations (in code).
   */
  public function _testSkinrSkinDefaults() {
    $uuid = '501ff0c3-db03-0944-9910-3a788f38097a';

    // Default skin configuration object should not exist yet.
    $this->assertFalse(skinr_skin_uuid_to_sid($uuid), 'Default skin configuration does not exist.');

    \Drupal::service('module_installer')->install(array('skinr_test_default'));

    // Test loading raw defaults.
    $default_skins = _skinr_skin_get_defaults();

    // Skin configuration object provided via hook_skinr_skin_defaults() in
    // skinr_test.skinr_default.inc.
    $this->assertTrue(isset($default_skins[$uuid]), 'Skin configuration in skinr_test.skinr_default.inc found.');
    unset($default_skins[$uuid]);

    // After asserting all expected, the list of default skins should be empty.
    $this->assertTrue(empty($default_skins), t('No unexpected skin configurations found: <pre>@data</pre>', array(
      '@data' => var_export($default_skins, TRUE),
    )));

    // Load a default skin configuration object.
    $skin = skinr_skin_load_by_uuid($uuid);
    $this->assertTrue($skin && $skin->uuid == $uuid, 'Successfully loaded default skin configuration.');

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE, 'Storage indicator indicates stored in code.');

    // Overridden status should not change storage indicator.
    $skin->status = 0;
    skinr_skin_save($skin);
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE, 'Storage indicator indicates stored in code.');

    // Override a default skin configuration object.
    $skin->status = 1;
    $skin->options = array('option3');
    skinr_skin_save($skin);

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE_OVERRIDDEN, 'Storage indicator indicates stored in code, but overridden in database.');

    // Revert a default skin configuration object.
    $this->assertTrue(skinr_skin_revert($skin->id()), 'Successfully reverted skin configuration to default.');
    // Refresh skin configuration data.
    $skin = skinr_skin_load_by_uuid($uuid);

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE, 'Storage indicator indicates stored in code.');

    // Test re-enabling module containing defaults; re-importing an existing
    // skin configuration.

    // Override default skin configuration.
    $skin->options = array('option3');
    skinr_skin_save($skin);

    // Disable, then re-enable module containing defaults.
    \Drupal::service('module_installer')->uninstall(array('skinr_test_default'));
    \Drupal::service('module_installer')->install(array('skinr_test_default'));

    // Refresh skin configuration data.
    $skin = skinr_skin_load_by_uuid($uuid);

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE_OVERRIDDEN, 'After enabling module containing already existing default skin configuration, storage indicator indicates stored in code, but overridden in database.');

    // Now test forced import.
    $default_skins = _skinr_skin_get_defaults();
    $default_skin = $default_skins[$uuid];
    $this->assertTrue(skinr_skin_import($default_skin, TRUE), 'Successfully forced import of existing skin configuration.');

    // Refresh skin configuration data.
    $skin = skinr_skin_load_by_uuid($uuid);

    // Test storage indicator.
    $this->assertTrue(skinr_skin_storage($skin) == SKINR_STORAGE_IN_CODE, 'After forcing import of existing default skin configuration, storage indicator indicates stored in code again.');
  }
}
