<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test the field permissions report page.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxLanguageTest extends UniqueFieldAjaxBase {

  /**
   * Tests unique field per language.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueFieldPerLang() {
    // Enable all the languages.
    foreach ($this->translationOptions as $lang_id => $name) {
      ConfigurableLanguage::createFromLangcode($lang_id)->save();
    }

    // Grab the translation options and shuffle, for fun.
    $translation_options = $this->translationOptions;
    $this->shuffleAssoc($translation_options);

    // Enable language settings.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->contentType, $edit, t('Save content type'));

    // Per-language not enabled, field has to be unique across languages.
    foreach ($this->fieldTypes as $field_type) {
      $this->createField($field_type['type'], $field_type['widget'],
        $field_type['settings']);
      $field_name = $this->fieldStorage->getName();

      $this->updateThirdPartySetting('unique', TRUE);
      $this->updateThirdPartySetting('per_lang', FALSE);

      $current_lang = 0;
      $edit = NULL;
      foreach ($translation_options as $lang_id => $name) {
        if (!$edit) {
          $edit = $this->createUpdateData($field_name, $field_type['value'],
            $field_type['effect'], $lang_id);
        }
        $edit['langcode[0][value]'] = $lang_id;

        // First language should save, the rest will fail.
        if ($current_lang === 0) {
          $this->canSaveField($edit);
        }
        else {
          $this->cannotSaveField($edit);
        }

        ++$current_lang;
      }
    }

    // Per-language enabled, field only has to be unique across same language.
    foreach ($this->fieldTypes as $field_type) {
      $this->createField($field_type['type'], $field_type['widget'],
        $field_type['settings']);
      $field_name = $this->fieldStorage->getName();

      $this->updateThirdPartySetting('unique', TRUE);
      $this->updateThirdPartySetting('per_lang', TRUE);

      $current_lang = 0;
      $edit = NULL;
      // Confirms across languages can contain the same information.
      foreach ($translation_options as $lang_id => $name) {
        if (!$edit) {
          $edit = $this->createUpdateData($field_name, $field_type['value'], $field_type['effect'], $lang_id);
        }
        $edit['langcode[0][value]'] = $lang_id;
        // Confirm we can save one but not another.
        // Then on second loop if "pre_lang" enabled, it can still be saved.
        $this->canSaveField($edit);
        $this->cannotSaveField($edit);

        ++$current_lang;
      }
    }
  }

  /**
   * Shuffles an array and keeps associated keys.
   *
   * @param array $array
   *   Array to shuffle.
   *
   * @return bool
   *   When complete
   */
  private function shuffleAssoc(array &$array): bool {
    $new = [];
    $keys = array_keys($array);

    shuffle($keys);

    foreach ($keys as $key) {
      $new[$key] = $array[$key];
    }

    $array = $new;

    return TRUE;
  }

}
