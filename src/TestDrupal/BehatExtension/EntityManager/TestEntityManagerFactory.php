<?php
namespace TestDrupal\BehatExtension\EntityManager;

use TestDrupal\BehatExtension\Context\RawTestDrupalContext;

class TestEntityManagerFactory extends RawTestDrupalContext {

  public function create($make, $model)
  {
    $manager = new TestEntityManager($make, $model);
    $manager->setBehatContext($this);
    return $manager;
  }
}




