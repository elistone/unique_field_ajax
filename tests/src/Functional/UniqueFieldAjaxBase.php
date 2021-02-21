<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;


/**
 * The base testing class for unique_field_ajax.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'language_test',
    'entity_test',
    'field_ui',
    'unique_field_ajax',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The field types to test upon.
   *
   * @var \string[][]
   */
  protected $fieldTypes = [];

  /**
   * Translation language options.
   *
   * @var string[]
   */
  protected $translationOptions = [];

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
  }

  /**
   * Helper method to create a field to use.
   *
   * @param $fieldType
   * @param $widgetType
   * @param array $fieldConfigSettings
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function _createField($fieldType, $widgetType, $fieldConfigSettings = []) {
    $fieldName = $this->_createRandomData();
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'type' => $fieldType,
    ]);
    $this->fieldStorage->save();

    $fieldConfig = [
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => $fieldName . '_label',
    ];
    if (!empty($fieldConfigSettings)) {
      $fieldConfig['settings'] = $fieldConfigSettings;
    }
    $this->field = FieldConfig::create($fieldConfig);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');

    $displayRepository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($fieldName, [
        'type' => $widgetType,
      ])
      ->save();
    $displayRepository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($fieldName)
      ->save();
  }

  /**
   * Runs a test to see if a field can be saved.
   *
   * @param $edit
   * @param bool $nid
   * @param null $language
   *
   * @return mixed
   */
  protected function _canSaveField($edit, $nid = FALSE, $language = NULL) {
    $method = $this->_getSaveMethod($nid, $language);
    $this->drupalPostForm($method, $edit, t('Save'));

    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);

    if (!empty($match)) {
      $id = $match[1];
      $msg = !$nid ? 'entity_test @id has been created.' : 'entity_test @id has been updated.';
      $this->assertText(t($msg, ['@id' => $id]));
      return $id;
    }
    else {
      var_dump($this->getUrl());
      var_dump($this->getSession()->getPage()->getHTML());
      $this->fail(t('Could not extract entity id from url'));
    }
  }

  /**
   * An Alias method for save field, requiring an nid;
   *
   * @param $edit
   * @param $nid
   * @param null $language
   *
   * @return mixed
   */
  protected function _canUpdateField($edit, $nid, $language = NULL) {
    return $this->_canSaveField($edit, $nid, $language);
  }

  /**
   * Runs a test to see if a field cannot be saved.
   *
   * @param $edit
   * @param null $customMsg
   * @param null $nid
   * @param null $language
   */
  protected function _cannotSaveField($edit, $customMsg = NULL, $nid = NULL, $language = NULL) {
    $method = $this->_getSaveMethod($nid, $language);
    $label_name = $this->field->label();

    $this->drupalPostForm($method, $edit, t('Save'));

    $msg = $customMsg ? t($customMsg) : t('The field @field has to be unique.', ['@field' => $label_name]);
    $this->assertText($msg);
  }

  /**
   * An Alias method for cannot updating field, requiring an nid;
   *
   * @param $edit
   * @param $nid
   * @param null $language
   * @param null $customMsg
   */
  protected function _cannotEditField($edit, $nid, $language = NULL, $customMsg = NULL) {
    $this->_cannotSaveField($edit, $customMsg, $nid, $language);
  }

  /**
   * Helper method to return the saving method of add or edit.
   *
   * @param $id
   * @param $language
   *
   * @return string
   */
  protected function _getSaveMethod($id, $language) {
    $path = !$id ? 'entity_test/add' : 'entity_test/manage/' . $id . '/edit';
    return $language ? $language . '/' . $path : $path;
  }

  /**
   * Helper method to update third part field settings.
   *
   * @param $key
   * @param $value
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function _updateThirdPartySetting($key, $value) {
    $this->field->setThirdPartySetting('unique_field_ajax', $key, $value);
    $this->field->save();
  }

  /**
   * Helper method to create update edit data.
   *
   * @param $fieldName
   * @param $value
   * @param $effect
   *
   * @return string[]
   */
  protected function _createUpdateData($fieldName, $value, $effect) {
    return ["{$fieldName}[0][{$effect}]" => $this->_createRandomData($value)];;
  }

  /**
   * Helper method to create random data.
   *
   * @param string $type
   */
  protected function _createRandomData($type = 'string') {
    $return = '';

    switch ($type) {
      case 'string':
        $return = mb_strtolower($this->randomMachineName());
        break;
      case 'sentence':
        $length = 200;
        $return = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
        $return = wordwrap($return, rand(3, 10), ' ', TRUE);
        break;
      case 'link':
        $return = 'http://www.' . $this->_createRandomData() . '.com/';
        break;
      case 'email':
        $return = $this->_createRandomData() . '@' . $this->_createRandomData() . '.com';
        break;
    }

    return $return;
  }

}
