<?php

use TestDrupal\BehatExtension\Context\RawTestDrupalEntityContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines application features from the specific context.
 */
class ArticleContext extends RawTestDrupalEntityContext
{

  public function __construct($contexts_map) {
    parent::__construct('node', 'article');
    parent::setContextsMap($contexts_map);
  }

  /**
   * Creates articles from a table.
   *
   * @Given articles:
   */
  public function addArticles(TableNode $datasetsTable) {

    parent::addMultipleFromTable($datasetsTable);
    /** @var $mink \Drupal\DrupalExtension\Context\MinkContext */
    $mink = $this->getContext('mink');
    $mink->visit("/user");
  }

}
