<?php

use TestDrupal\BehatExtension\Context\RawTestDrupalEntityContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines application features from the specific context.
 */
class CustomerContext extends RawTestDrupalEntityContext
{

  public function __construct() {
    parent::__construct('user', 'user');
  }

  /**
   * Creates articles from a table.
   *
   * @Given customers:
   */
  public function addCustomers(TableNode $datasetsTable) {
    parent::addMultipleFromTable($datasetsTable);
  }

}
