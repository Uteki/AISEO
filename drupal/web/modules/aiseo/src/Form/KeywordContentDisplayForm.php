<?php

namespace Drupal\aiseo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the keyword and content display form.
 */
class KeywordContentDisplayForm extends FormBase {

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
    return 'keyword_content_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $keywords = $this->getKeywords();
    $content = $this->getContent();
    $actionRows = $this->getContentActionRows();

    // Show keywords
    $form['keywords'] = [
      '#type' => 'checkboxes',
      '#title' => 'Keywords',
      '#rows' => 5,
      '#options' => array_combine($keywords, $keywords),
    ];

    $form['actions']['delete_keywords'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ausgewählte Keywords löschen'),
      '#submit' => ['::deleteKeywordsSubmit'],
      '#attributes' => ['class' => ['button']],
    ];

    // Show content
    $form['content'] = [
      '#type' => 'item',
      '#title' => 'Inhalte',
      '#markup' => '<div class="content-container" style="height: 30px; overflow-y: scroll;"><table><tr><th>ID</th><th>Title</th><th>Meta</th><th>Content</th></tr>' . $content . '</table></div>',    ];

    $form['content']['delete_content'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getContentIds(),
    ];

    $form['content']['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-actions']],
    ];

    $form['content']['actions']['delete_content'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ausgewählte Inhalte löschen'),
      '#submit' => ['::deleteContentSubmit'],
      '#attributes' => ['class' => ['button']],
    ];

    $form['redirect'] = [
      '#type' => 'link',
      '#title' => 'Zurück zum Anfang!',
      '#url' => \Drupal\Core\Url::fromUri('internal:/aiseo'),
    ];

    $form['#attached']['library'][] = 'aiseo/custom_seo_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Add validation logic if necessary
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submission logic needed for this form
  }

  /**
   * Retrieves the keywords from the database.
   *
   * @return array
   *   An array of keywords.
   */
  protected function getKeywords() {
    $query = $this->connection->select('keywords', 'k')
      ->fields('k', ['name'])
      ->orderBy('k.name', 'ASC');
    $keywords = $query->execute()->fetchCol();
    return $keywords;
  }

  /**
   * Retrieves the content from the openai_data table.
   *
   * @return string
   *   The HTML markup for displaying the content in a table.
   */
  protected function getContent() {
    $query = $this->connection->select('openai_data', 'o')
      ->fields('o', ['id', 'title', 'meta', 'content'])
      ->orderBy('o.id', 'ASC');
    $results = $query->execute()->fetchAll();

    $content = '';
    foreach ($results as $result) {
      $content .= '<tr>';
      $content .= '<td>' . $result->id . '</td>';
      $content .= '<td>' . $result->title . '</td>';
      $content .= '<td>' . $result->meta . '</td>';
      $content .= '<td>' . $result->content . '</td>';
      $content .= '</tr>';
    }

    return $content;
  }

  /**
   * Funktion zum Generieren der Action-Spalten für jeden Inhalt.
   *
   * @return string
   *   Der HTML-Markup für die Action-Spalten.
   */
  protected function getContentActionRows() {
    $query = $this->connection->select('openai_data', 'o')
      ->fields('o', ['id'])
      ->orderBy('o.id', 'ASC');
    $results = $query->execute()->fetchAll();

    $actionRows = '';
    foreach ($results as $result) {
      $actionRows .= '<tr>';
      $actionRows .= '<td colspan="3"></td>';
      $actionRows .= '<td><input type="checkbox" name="delete_content[]" value="' . $result->id . '"></td>';
      $actionRows .= '</tr>';
    }

    return $actionRows;
  }


  protected function getContentIds() {
    $query = $this->connection->select('openai_data', 'o')
      ->fields('o', ['id'])
      ->orderBy('o.id', 'ASC');
    $results = $query->execute()->fetchAll();

    $content_ids = [];
    foreach ($results as $result) {
      $content_ids[$result->id] = $result->id;
    }

    return $content_ids;
  }

  /**
   * Form submission handler for deleting keywords.
   */
  public function deleteKeywordsSubmit(array &$form, FormStateInterface $form_state) {
    $delete_keywords = array_filter($form_state->getValue('keywords'));
    if (!empty($delete_keywords)) {
      $this->connection->delete('keywords')
        ->condition('name', $delete_keywords, 'IN')
        ->execute();

      \Drupal::messenger()->addMessage($this->t('Gewählte Keywords gelöscht.'));
    }
  }

  /**
   * Form submission handler for deleting content.
   */
  public function deleteContentSubmit(array &$form, FormStateInterface $form_state) {
    $delete_ids = array_filter($form_state->getValue('delete_content'));
    if (!empty($delete_ids)) {
      $this->connection->delete('openai_data')
        ->condition('id', $delete_ids, 'IN')
        ->execute();

      \Drupal::messenger()->addMessage($this->t('Gewählte Inhalt gelöscht.'));
    }
  }
}
