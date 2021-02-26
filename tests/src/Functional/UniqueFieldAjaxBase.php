<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
    'node',
    'language',
    'language_test',
    'field_ui',
    'unique_field_ajax',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default content type.
   *
   * @var string
   */
  protected $contentType = 'node_unique_field_ajax';

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
  protected $fieldTypes = [
    'string' => [
      'type' => 'string',
      'widget' => 'string_textfield',
      'value' => 'string',
      'effect' => 'value',
      'settings' => [],
    ],
    'email' => [
      'type' => 'email',
      'widget' => 'email_default',
      'value' => 'email',
      'effect' => 'value',
      'settings' => [],
    ],
  ];

  /**
   * Translation language options.
   *
   * @var string[]
   */
  protected $translationOptions = [
    'es' => 'spanish',
    'fr' => 'french',
    'de' => 'german',
  ];

  /**
   * Perform initial setup tasks that run before every test method.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
    $this->createCustomContentType();
  }

  /**
   * Create a new content type using the content type variable.
   */
  protected function createCustomContentType() {
    $this->drupalCreateContentType(['type' => $this->contentType]);
  }

  /**
   * Helper method to create a field to use.
   *
   * @param string $fieldType
   *   Type of field.
   * @param string $widgetType
   *   Type of field widget.
   * @param array $fieldConfigSettings
   *   Any extra field config settings.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createField(string $fieldType, string $widgetType, array $fieldConfigSettings = []) {
    $field_name = $this->createRandomData();
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $fieldType,
    ]);
    $this->fieldStorage->save();

    $field_config = [
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->contentType,
      'label' => $field_name . '_label',
    ];
    if (!empty($fieldConfigSettings)) {
      $field_config['settings'] = $fieldConfigSettings;
    }
    $this->field = FieldConfig::create($field_config);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name, [
        'type' => $widgetType,
      ])
      ->save();
    $display_repository->getViewDisplay('node', $this->contentType, 'full')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Runs a test to see if a field can be saved.
   *
   * @param array $edit
   *   Edit data.
   * @param bool $nid
   *   Node id.
   *
   * @return int
   *   Saved/updated node id.
   */
  protected function canSaveField(array $edit, $nid = FALSE): int {
    $title = $edit['title[0][value]'];
    $method = $this->getSaveMethod($nid);
    $this->drupalPostForm($method, $edit, t('Save'));

    preg_match('|node/(\d+)|', $this->getUrl(), $match);

    if (!empty($match)) {
      $id = $match[1];
      if (!$nid) {
        $this->assertText(t('@contentType @title has been created.',
          ['@title' => $title, '@contentType' => $this->contentType])
        );
      }
      else {
        $this->assertText(t('@contentType @title has been updated.',
            ['@title' => $title, '@contentType' => $this->contentType])
              );
      }
      return (int) $id;
    }
    else {
      var_dump($this->getUrl());
      var_dump($this->getSession()->getPage()->getHtml());
      static::fail(t('Could not extract entity id from url'));
    }
    return -1;
  }

  /**
   * An Alias method for save field, requiring an nid.
   *
   * @param array $edit
   *   Edit data.
   * @param string $nid
   *   Node id.
   *
   * @return int
   *   Saved/updated node id.
   */
  protected function canUpdateField(array $edit, string $nid): int {
    return $this->canSaveField($edit, $nid);
  }

  /**
   * Runs a test to see if a field cannot be saved.
   *
   * @param array $edit
   *   Edit data.
   * @param string $customMsg
   *   Custom save message.
   * @param string $nid
   *   Node id.
   */
  protected function cannotSaveField(array $edit, $customMsg = NULL, $nid = NULL) {
    $method = $this->getSaveMethod($nid);
    $label_name = $this->field->label();

    $this->drupalPostForm($method, $edit, t('Save'));
    if ($customMsg) {
      $message = $customMsg;
    }
    else {
      $message = t('The field @field has to be unique.', ['@field' => $label_name]);
    }
    $this->assertText($message);
  }

  /**
   * An Alias method for cannot updating field, requiring an nid.
   *
   * @param array $edit
   *   Edit data.
   * @param string $nid
   *   Node id.
   * @param string $customMsg
   *   Custom save message.
   */
  protected function cannotEditField(array $edit, string $nid, $customMsg = NULL) {
    $this->cannotSaveField($edit, $customMsg, $nid);
  }

  /**
   * Helper method to return the saving method of add or edit.
   *
   * @param string|null $id
   *   Node id.
   *
   * @return string
   *   Method path.
   */
  protected function getSaveMethod(string $id = NULL): string {
    return !$id ? 'node/add/' . $this->contentType : 'node/' . $id . '/edit';
  }

  /**
   * Helper method to update third part field settings.
   *
   * @param string $key
   *   Third Party key.
   * @param string $value
   *   Third Party value.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateThirdPartySetting(string $key, string $value) {
    $this->field->setThirdPartySetting('unique_field_ajax', $key, $value);
    $this->field->save();
  }

  /**
   * Helper method to create update edit data.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $value
   *   Field value.
   * @param string $effect
   *   Type of field.
   * @param string|null $language
   *   Optional language settings.
   *
   * @return string[]
   *   Edit data formatted for submit.
   */
  protected function createUpdateData(string $fieldName, string $value, string $effect, string $language = NULL): array {
    $return = [];
    $return['title[0][value]'] = $this->randomString();
    $return['body[0][value]'] = $this->randomString();
    $return["{$fieldName}[0][{$effect}]"] = $this->createRandomData($value);
    if ($language) {
      $return['langcode[0][value]'] = $language;
    }
    return $return;
  }

  /**
   * Helper method to create random data.
   *
   * @param string $type
   *   Type of random data.
   *
   * @return false|string|string[]
   *   Random data.
   */
  protected function createRandomData($type = 'string') {
    $return = '';

    switch ($type) {
      case 'string':
        $return = mb_strtolower($this->randomMachineName());
        break;

      case 'sentence':
        $length = 200;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $return = substr(str_shuffle(str_repeat($chars,
          ceil($length / strlen($chars)))), 1, $length);
        $return = wordwrap($return, rand(3, 10), ' ', TRUE);
        break;

      case 'link':
        $return = 'http://www.' . $this->createRandomData() . '.com/';
        break;

      case 'email':
        $return = $this->createRandomData() . '@' . $this->createRandomData() . '.com';
        break;
    }

    return $return;
  }

}
