<?php

namespace Drupal\aiseo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the keyword import form.
 */
class KeywordImportForm extends FormBase {

  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'keyword_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Keywords'),
      '#description' => $this->t('Geben Sie die Keywords ein, einer pro Zeile.'),
      '#required' => FALSE,
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload File'),
      '#description' => $this->t('Laden Sie eine Datei mit Keywords hoch.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importieren'),
    ];

    $form['redirect'] = [
      '#type' => 'link',
      '#title' => 'Zurück zum Anfang!',
      '#url' => \Drupal\Core\Url::fromUri('internal:/aiseo'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($file[0])) {
      $file_entity = \Drupal\file\Entity\File::load($file[0]);
      $file_contents = file_get_contents($file_entity->getFileUri());

      if ($file_contents !== FALSE) {
        $file_keywords = explode("\n", $file_contents);
        $existingKeywords = $this->getExistingKeywords();
        $duplicateKeywords = [];

        foreach ($file_keywords as $keyword) {
          $keyword = trim($keyword);

          if (!empty($keyword)) {
            if (in_array($keyword, $existingKeywords)) {
              $duplicateKeywords[] = $keyword;
            }
          }
        }

        if (!empty($duplicateKeywords)) {
          $duplicateKeywordsList = implode(', ', $duplicateKeywords);
          $form_state->setErrorByName('file', $this->t('The following keywords already exist and cannot be imported: @keywords', ['@keywords' => $duplicateKeywordsList]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keywords = $form_state->getValue('keywords');
    $file = $form_state->getValue('file');

    if (!empty($file[0])) {
      $file_entity = \Drupal\file\Entity\File::load($file[0]);
      $file_contents = file_get_contents($file_entity->getFileUri());

      if ($file_contents !== FALSE) {
        $file_keywords = explode("\n", $file_contents);

        foreach ($file_keywords as $keyword) {
          $keyword = trim($keyword);

          if (!empty($keyword)) {
            $this->insertKeyword($keyword);
          }
        }
      }
    }

    $keywordsArray = explode("\n", $keywords);
    $importedKeywords = [];

    foreach ($keywordsArray as $keyword) {
      $keyword = trim($keyword);

      if (!empty($keyword)) {
        if (!$this->isKeywordExists($keyword)) {
          $this->insertKeyword($keyword);
          $importedKeywords[] = $keyword;
        }
      }
    }

    //\Drupal::messenger()->addMessage($this->t('Keywords erfolgreich importiert.'));

    if (!empty($importedKeywords)) {
      $importedKeywordsList = implode(', ', $importedKeywords);
      \Drupal::messenger()->addMessage($this->t('Die folgenden Keywords wurden erfolgreich importiert: @keywords', ['@keywords' => $importedKeywordsList]));
    } else {
      \Drupal::messenger()->addMessage($this->t('Keine neuen Keywords wurden aus der Eingabe importiert.'));
    }
  }

  /**
   * Retrieves existing keywords from the database.
   *
   * @return array
   *   An array of existing keywords.
   */
  protected function getExistingKeywords() {
    $query = $this->connection->select('keywords', 'k')
      ->fields('k', ['name']);
    $result = $query->execute();

    $existingKeywords = [];
    foreach ($result as $row) {
      $existingKeywords[] = $row->name;
    }

    return $existingKeywords;
  }

  /**
   * Inserts a keyword into the database.
   *
   * @param string $keyword
   *   The keyword to insert.
   */
  protected function insertKeyword($keyword) {
    $this->connection->insert('keywords')
      ->fields(['name' => $keyword])
      ->execute();
  }

  /**
   * Checks if a keyword already exists in the database.
   *
   * @param string $keyword
   *   The keyword to check.
   *
   * @return bool
   *   TRUE if the keyword already exists, FALSE otherwise.
   */
  protected function isKeywordExists($keyword) {
    $query = $this->connection->select('keywords', 'k')
      ->fields('k', ['name'])
      ->condition('name', $keyword)
      ->range(0, 1);

    return (bool) $query->execute()->fetchField();
  }
}
