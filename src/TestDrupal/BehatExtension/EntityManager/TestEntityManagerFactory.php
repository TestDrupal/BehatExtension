<?php
namespace TestDrupal\BehatExtension\EntityManager;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\EntityInterface;
use TestDrupal\BehatExtension\ServiceContainer\Page;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Symfony\Component\Config\Definition\Exception\Exception;
use Drupal\Core\Entity\EntityTypeBundleInfo;



class TestEntityManagerFactory extends RawTestDrupalContext {

  public function create($make, $model)
  {
    $manager = new TestEntityManager($make, $model);
    $manager->setBehatContext($this);
    return $manager;
  }
}




