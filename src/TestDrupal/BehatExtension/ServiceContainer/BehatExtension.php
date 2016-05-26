<?php

namespace TestDrupal\BehatExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Drupal\Driver\Exception\Exception;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Drupal\DrupalExtension;

class BehatExtension implements ExtensionInterface {

  const TestDrupal_ID = 'testdrupal';



  /**
   * Returns the extension config key.
   *
   * @return string
   */
  public function getConfigKey(){
    return self::TestDrupal_ID;
  }

  /**
   * Initializes other extensions.
   *
   * This method is called immediately after all extensions are activated but
   * before any extension `configure()` method is called. This allows extensions
   * to hook into the configuration of other extensions providing such an
   * extension point.
   *
   * @param ExtensionManager $extensionManager
   */
  public function initialize(ExtensionManager $extensionManager) {
    $drupalExt = $extensionManager->getExtension("drupal");
    if (!isset($drupalExt)) {
      throw new \Exception("The Drupal Extension must be installed and configured to use the DrupalEntityExtension.");
    }
  }

  /**
   * Setups configuration for the extension.
   *
   * @param ArrayNodeDefinition $builder
   */
  public function configure(ArrayNodeDefinition $builder) {
    $builder->
      children()->
        arrayNode('default_contexts_map')->
          info('The class to use to store entities between steps.')->
          useAttributeAsKey('key')->
          prototype('variable')->
        end()->
      end()->
    end();
  }

  /**
   * Loads extension services into temporary container.
   *
   * @param ContainerBuilder $container
   * @param array            $config
   */
  public function load(ContainerBuilder $container, array $config) {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
    $loader->load('services.yml');

    $container->setParameter('testdrupal.default_contexts_map', $config['default_contexts_map']);

    # Override the DrupalExtension's Hook loader so we can add our own hooks.
    $container->setParameter('drupal.context.annotation.reader.class',
      'TestDrupal\BehatExtension\Context\Annotation\Reader');

    //$this->loadStoreListener($container);

  }

  public function process(ContainerBuilder $container) {
   $i = 'test';
  }

  /*private function loadStoreListener(ContainerBuilder $container)
  {
    $definition = new Definition('TestDrupal\BehatExtension\Listener\StoreListener', array(
      new Reference(self::TestDrupal_ID),
      '%testdrupal.page_store%',
      '%mink.javascript_session%',
      '%mink.available_javascript_sessions%',
    ));
    $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, array('priority' => 0));
    $container->setDefinition('mink.listener.sessions', $definition);
  }*/
}
