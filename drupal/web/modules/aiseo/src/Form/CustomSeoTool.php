<?php

namespace Drupal\aiseo\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomSeoTool extends FormBase {

  protected $connection;
  protected $httpClient;

  public function __construct(Connection $connection, Client $httpClient) {
    $this->connection = $connection;
    $this->httpClient = $httpClient;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return 'seo_custom_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = $this->connection->select('keywords', 'k');
    $query->fields('k', ['id', 'name']);
    $keywords = $query->execute()->fetchAllKeyed();

    $selectedKeywords = $form_state->getValue('keywords') ?? [];

    $form['titel'] = [
      '#type' => 'textfield',
      '#title' => 'Page Titel',
      '#description' => 'Gibt einen titel aus',
      '#required' => false,
    ];

    $form['keywordsTitel'] = [
      '#type' => 'select',
      '#title' =>'Keywords für Titel',
      '#options' => $keywords,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="titel"]' => ['filled' => FALSE],
        ],
      ],
    ];

    $form['meta'] = [
      '#type' => 'textfield',
      '#title' => 'Meta Description',
      '#description' => 'Gibt einen meta aus',
      '#required' => false,
    ];

    $form['keywordsMeta'] = [
      '#type' => 'select',
      '#title' =>'Keywords für Meta',
      '#options' => $keywords,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="meta"]' => ['filled' => FALSE],
        ],
      ],
    ];

    $form['info'] = [
      '#type' => 'textfield',
      '#title' => 'Artikel / Inhalt / Info Text',
      '#description' => 'Gibt einen info bzw. einen Artikel aus',
      '#required' => false,
    ];

    $form['keywordsInfo'] = [
      '#type' => 'select',
      '#title' =>'Keywords für Artikel / Info',
      '#options' => $keywords,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="info"]' => ['filled' => FALSE],
        ],
      ],
    ];

    $form['submitted_values'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="submitted-values">',
      '#suffix' => '</div>',
      '#markup' => isset($form_state->getStorage()['submitted_values']) ? $form_state->getStorage()['submitted_values'] : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Senden',
    ];

    $form['redirecA'] = [
      '#type' => 'link',
      '#title' => 'Möchten Sie Keywords importieren?',
      '#url' => \Drupal\Core\Url::fromUri('internal:/aiseo/keyword-import'),
    ];

    $form['redirectB'] = [
      '#type' => 'link',
      '#title' => 'Verwalte den zuvor gespeicherten Inhalt.',
      '#url' => \Drupal\Core\Url::fromUri('internal:/aiseo/content-display'),
    ];

    $form['#attached']['library'][] = 'aiseo/custom_seo_form';

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('titel');
    $meta = $form_state->getValue('meta');
    $info = $form_state->getValue('info');

    // Validation could be done in this function

    // if (strlen($title) <= 5) {
    //   $form_state->setErrorByName('titel', "Please make sure your title length is more than 5.");
    // }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage("Angaben wurden erfolgreich übermittelt");

    $key = xxxxx;

    $prompt1 = $form_state->getValue('titel');
    $prompt2 = $form_state->getValue('meta');
    $prompt3 = $form_state->getValue('info');

    $keywords1 = $form_state->getValue('keywordsTitel');
    $keywords2 = $form_state->getValue('keywordsMeta');
    $keywords3 = $form_state->getValue('keywordsInfo');

    $selectedKeywords1 = $this->getKeywordNames(array_filter($form_state->getValue('keywordsTitel')));
    $selectedKeywords2 = $this->getKeywordNames(array_filter($form_state->getValue('keywordsMeta')));
    $selectedKeywords3 = $this->getKeywordNames(array_filter($form_state->getValue('keywordsInfo')));

    if (empty($prompt1)) {
      $prompt1 = !empty($selectedKeywords1) ? implode(', ', $selectedKeywords1) : $keywords1;
    }
    if (empty($prompt2)) {
      $prompt2 = !empty($selectedKeywords2) ? implode(', ', $selectedKeywords2) : $keywords2;
    }
    if (empty($prompt3)) {
      $prompt3 = !empty($selectedKeywords3) ? implode(', ', $selectedKeywords3) : $keywords3;
    }

    if (empty($prompt1) && empty($keywords1)) {
      $prompt1 = '';
    }
    if (empty($prompt2) && empty($keywords2)) {
      $prompt2 = '';
    }
    if (empty($prompt3) && empty($keywords3)) {
      $prompt3 = '';
    }

    if (!empty($prompt1) || !empty($keywords1)) {
      $result1 = $this->callOpenAiApi('https://api.openai.com/v1/engines/text-davinci-003/completions', $key, 'SEO Optimierter Title mit den Keyword: ' . $prompt1, 77);
    }
    if (!empty($prompt2) || !empty($keywords2)) {
      $result2 = $this->callOpenAiApi('https://api.openai.com/v1/engines/text-davinci-003/completions', $key, 'SEO Optimierter Meta Description mit den Keyword: ' . $prompt2, 155);
    }
    if (!empty($prompt3) || !empty($keywords3)) {
      $result3 = $this->callOpenAiApi('https://api.openai.com/v1/engines/text-davinci-003/completions',  $key, 'SEO Optimierter Artikel / Inhalt /Info Text mit den Keyword: ' . $prompt3, 333);
    }

    // Saves the OpenAI-Data in the database
    if (!empty($result1) || !empty($result2) || !empty($result3)) {
      $this->saveOpenAiContent($result1, $result2, $result3);
    }

    // Display values and OpenAI output
    $markup = 'Submitted Values:<br><br>';
    $markup .= 'Page Titel: ' . $prompt1 . '<br>';
    $markup .= 'OpenAI Output:<br>';
    $markup .= 'Ergebnis 1: ' . $result1 . '<br><br>';
    $markup .= 'Meta Description: ' . $prompt2 . '<br>';
    $markup .= 'OpenAI Output:<br>';
    $markup .= 'Ergebnis 2: ' . $result2 . '<br><br>';
    $markup .= 'Artikel / Inhalt / Info Text: ' . $prompt3 . '<br>';
    $markup .= 'OpenAI Output:<br><br>';
    $markup .= 'Ergebnis 3: ' . $result3;

    $form_state->setStorage(['submitted_values' => $markup]);
    $form_state->setRebuild();
  }

  protected function saveOpenAiContent($result1, $result2, $result3) {
    $maxContentLength1 = 255;
    $maxContentLength2 = 500;
    $maxContentLength3 = 5000;

    if (!empty($result1) || !empty($result2) || !empty($result3)) {
      // Database connection
      $connection = \Drupal::database();

      $table = 'openai_data';

      $insertData = [];

      if (!empty($result1) && strlen($result1) <= $maxContentLength1) {
        $insertData['title'] = $result1;
      }

      if (!empty($result2) && strlen($result2) <= $maxContentLength2) {
        $insertData['meta'] = $result2;
      }

      if (!empty($result3) && strlen($result3) <= $maxContentLength3) {
        $insertData['content'] = $result3;
      }

      if (!empty($insertData)) {
        $connection->insert($table)
          ->fields($insertData)
          ->execute();

        \Drupal::messenger()->addMessage($this->t('Generierter Text gespeichert.'));
      } else {
        \Drupal::messenger()->addError($this->t('Generierter Text nicht gespeichert. Der Inhalt ist zu lang.'));
      }
    }
  }

  protected function getKeywordNames($keywordIds) {
    $names = [];
    if (!empty($keywordIds)) {
      $query = $this->connection->select('keywords', 'k');
      $query->addField('k', 'name');
      $query->condition('k.id', $keywordIds, 'IN');
      $results = $query->execute()->fetchAll();
      foreach ($results as $result) {
        $names[] = $result->name;
      }
    }
    return $names;
  }

  protected function callOpenAiApi($url, $apiKey, $prompt, $maxTokens) {
    try {
      $response = $this->httpClient->post($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'prompt' => $prompt,
          'max_tokens' => $maxTokens,
        ],
      ]);

      if (!is_object($response)) {
        throw new Exception('Response invalid.');
      }

      $result = json_decode($response->getBody(), true);

      if (!isset($result['choices'][0]['text'])) {
        throw new Exception('Answer missing.');
      }

      return $result['choices'][0]['text'];

    } catch (RequestException $e) {
      // Handle API request error
      $this->messenger()->addError('API request error: ' . $e->getMessage());
    }
  }

}
