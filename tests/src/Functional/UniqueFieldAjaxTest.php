<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

/**
 * Test the field permissions report page.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxTest extends UniqueFieldAjaxBase {

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
    'es' => 'name spanish',
    'fr' => 'name french',
  ];

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
  }


  /**
   * Tests unique field option.
   * Tests Include:
   *  - Field not enabled does not cause issues.
   *  - Field enabled requires value to be unique.
   *  - Field does not get triggered as unique if edited and saved.
   *  - Tests are run across multiple field types.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueField() {
    foreach ($this->fieldTypes as $fieldType) {
      $this->_createField($fieldType['type'], $fieldType['widget'], $fieldType['settings']);
      $fieldName = $this->fieldStorage->getName();

      // Field not enabled does not cause issues.
      $this->_updateThirdPartySetting('unique', FALSE);
      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
      $this->_canSaveField($edit);
      $this->_canSaveField($edit);
      $this->_canSaveField($edit);
      $this->_canSaveField($edit);

      // Field enabled requires value to be unique.
      $this->_updateThirdPartySetting('unique', TRUE);
      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
      $this->_canSaveField($edit);
      $this->_cannotSaveField($edit);

      // Field does not get triggered as unique if edited and saved.
      $this->_updateThirdPartySetting('unique', TRUE);
      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
      $id = $this->_canSaveField($edit);
      $this->_canUpdateField($edit, $id);
      $this->_canUpdateField($edit, $id);
    }
  }


  /**
   * Tests unique field custom message.
   * Tests Include:
   *  - With a custom message this is presented if errors.
   *  - Not adding a custom message falls back to default.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueFieldCustomMessage() {
    foreach ($this->fieldTypes as $fieldType) {
      $this->_createField($fieldType['type'], $fieldType['widget'], $fieldType['settings']);
      $fieldName = $this->fieldStorage->getName();

      // Create a random sentence.
      $msg = $this->_createRandomData('sentence');

      // With a custom message this is presented if errors.
      $this->_updateThirdPartySetting('unique', TRUE);
      $this->_updateThirdPartySetting('message', $msg);
      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
      $this->_canSaveField($edit);
      $this->_cannotSaveField($edit, $msg);

      // Not adding a custom message falls back to default.
      $this->_updateThirdPartySetting('unique', TRUE);
      $this->_updateThirdPartySetting('message', '');
      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
      $this->_canSaveField($edit);
      $this->_cannotSaveField($edit);
    }
  }

}
