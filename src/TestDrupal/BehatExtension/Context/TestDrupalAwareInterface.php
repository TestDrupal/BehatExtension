<?php

namespace TestDrupal\BehatExtension\Context;

use TestDrupal\BehatExtension\ServiceContainer\EntityStore;
use TestDrupal\BehatExtension\ServiceContainer\PageStore;
use Drupal\DrupalExtension\Context\DrupalAwareInterface;

interface TestDrupalAwareInterface extends DrupalAwareInterface {

  /**
   * Sets EntityStore instance.
   * @param $store
   * @return
   */
  public function setEntityStore(EntityStore $store);

  /**
   * Sets Page Store instance.
   * @param $store
   * @return
   */
  public function setPageStore(PageStore $store);


  public function setContextsMap(array $contexts_map);

  public function getContextsMap();

}
