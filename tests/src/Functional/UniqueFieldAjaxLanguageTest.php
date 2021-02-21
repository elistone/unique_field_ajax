<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

/**
 * Test the field permissions report page.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxLanguageTest extends UniqueFieldAjaxBase {

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
  }


  /**
   * Tests unique field per language.
   * Tests Include:
   *  - Field not enabled does not test uniqueness across languages.
   *  - Field enabled requires uniqueness no matter the language.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueFieldPerLang() {
    // TODO: Setup so that we are testing per language option
//    foreach ($this->translationOptions as $langId => $name) {
//      ConfigurableLanguage::create(['id' => $langId])->save();
//    }
//
//    $this->rebuildContainer();
//
//    foreach ($this->fieldTypes as $fieldType) {
//      $this->_createField($fieldType['type'], $fieldType['widget'], $fieldType['settings']);
//      $fieldName = $this->fieldStorage->getName();
//
//      //      $languageEdits = [];
//      //      foreach ($this->translationOptions as $langId => $name) {
//      //        $languageEdits[$langId] = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
//      //      }
//
//      $this->_updateThirdPartySetting('unique', TRUE);
//      $this->_updateThirdPartySetting('per_lang', TRUE);
//      $edit = $this->_createUpdateData($fieldName, $fieldType['value'], $fieldType['effect']);
//      foreach ($this->translationOptions as $langId => $name) {
//        $this->_canSaveField($edit);
//        //        $this->_canSaveField($edit, NULL, $langId);
//        //        $this->_cannotSaveField($edit, NULL, NULL, $langId);
//      }
//
//      //      foreach ($this->translationOptions as $langId => $name) {
//      //        $edit = $languageEdits[$langId];
//      //        $this->_cannotSaveField($edit, NULL, NULL, $langId);
//      //      }
//    }
  }

}
