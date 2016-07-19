<?php

namespace TestDrupal\BehatExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Context\Context;
use TestDrupal\BehatExtension\Context\TestDrupalAwareInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class TestDrupalAwareInitializer extends RawDrupalContext implements ContextInitializer {
  private $entityStore, $pageStore, $parameters;

  public function __construct($entityStore, $pageStore, $entityManagerFactory, array $parameters, array $default_contexts_map) {
    $this->entityStore = $entityStore;
    $this->pageStore = $pageStore;
    $this->parameters = $parameters;
    $this->default_contexts_map = $default_contexts_map;
    $this->entityManagerFactory = $entityManagerFactory;
  }

  /**
   * {@inheritdocs}
   */
  public function initializeContext(Context $context) {

    // All contexts are passed here, only RawTestDrupalEntityContext is allowed.
    if (!$context instanceof TestDrupalAwareInterface) {
      return;
    }
    $context->setEntityStore($this->entityStore);
    $context->setPageStore($this->pageStore);
    $context->setEntityManagerFactory($this->entityManagerFactory);

    // Set the default contexts that should be available.
    if(isset($this->default_contexts_map) && is_array($this->default_contexts_map)) {
      $map = $context->getContextsMap();
      // Override any defaults with whatever the existing values were.
      foreach($map as $key => $class) {
        $this->default_contexts_map[$key] = $class;
      }
      // Then update the Contexts Map
      $context->setContextsMap($this->default_contexts_map);
    }

    // Add all parameters to the context.
    //$context->setParameters($this->parameters);
  }

}
