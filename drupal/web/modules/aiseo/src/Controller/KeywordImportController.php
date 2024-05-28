<?php

namespace Drupal\aiseo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class KeywordImportController extends ControllerBase {
  public function content() {
    // Erstelle das Keyword-Import-Formular
    $form = \Drupal::formBuilder()->getForm('Drupal\aiseo\Form\KeywordImportForm');

    // Rendere das Formular als HTML
    $renderer = \Drupal::service('renderer');
    $form_html = $renderer->render($form);

    // Gib das Formular als Ajax-Antwort zurück
    return new Response($form_html);
  }
}
