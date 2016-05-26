<?php

namespace TestDrupal\BehatExtension\Hook\Call;

use TestDrupal\BehatExtension\Hook\Scope\TestDrupalEntityScope;
use Drupal\DrupalExtension\Hook\Call\EntityHook;

/**
 * BeforeNodeCreate hook class.
 */
class AfterTestDrupalEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = null) {
    parent::__construct(TestDrupalEntityScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterTestDrupalEntityCreate';
  }
}
