<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

/**
 * Test the field permissions report page.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxTest extends UniqueFieldAjaxBase {

  /**
   * Tests unique field option.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueField() {
    foreach ($this->fieldTypes as $field_type) {
      $this->createField($field_type['type'], $field_type['widget'],
        $field_type['settings']);
      $field_name = $this->fieldStorage->getName();

      // Field not enabled does not cause issues.
      $this->updateThirdPartySetting('unique', FALSE);
      $edit = $this->createUpdateData($field_name, $field_type['value'],
        $field_type['effect']);
      $this->canSaveField($edit);
      $this->canSaveField($edit);
      $this->canSaveField($edit);
      $this->canSaveField($edit);

      // Field enabled requires value to be unique.
      $this->updateThirdPartySetting('unique', TRUE);
      $edit = $this->createUpdateData($field_name, $field_type['value'],
        $field_type['effect']);
      $this->canSaveField($edit);
      $this->cannotSaveField($edit);

      // Field does not get triggered as unique if edited and saved.
      $this->updateThirdPartySetting('unique', TRUE);
      $edit = $this->createUpdateData($field_name, $field_type['value'],
        $field_type['effect']);
      $id = $this->canSaveField($edit);
      $this->canUpdateField($edit, $id);
      $this->canUpdateField($edit, $id);
    }
  }

  /**
   * Tests unique field custom message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueFieldCustomMessage() {
    foreach ($this->fieldTypes as $field_type) {
      $this->createField($field_type['type'], $field_type['widget'],
        $field_type['settings']);
      $field_name = $this->fieldStorage->getName();

      // Create a random sentence.
      $msg = $this->createRandomData('sentence');

      // With a custom message this is presented if errors.
      $this->updateThirdPartySetting('unique', TRUE);
      $this->updateThirdPartySetting('message', $msg);
      $edit = $this->createUpdateData($field_name, $field_type['value'],
        $field_type['effect']);
      $this->canSaveField($edit);
      $this->cannotSaveField($edit, $msg);

      // Not adding a custom message falls back to default.
      $this->updateThirdPartySetting('unique', TRUE);
      $this->updateThirdPartySetting('message', '');
      $edit = $this->createUpdateData($field_name, $field_type['value'],
        $field_type['effect']);
      $this->canSaveField($edit);
      $this->cannotSaveField($edit);
    }
  }

}
