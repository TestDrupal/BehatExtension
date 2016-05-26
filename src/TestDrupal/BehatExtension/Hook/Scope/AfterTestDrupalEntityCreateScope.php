<?php
/**
 * @file
 * Entity scope.
 */
namespace TestDrupal\BehatExtension\Hook\Scope;

use Behat\Testwork\Hook\Scope\HookScope;

/**
 * Represents an Entity hook scope.
 */
final class AfterTestDrupalEntityCreateScope extends TestDrupalEntityScope {

  /**
   * Return the scope name.
   *
   * @return string
   */
  public function getName() {
    return self::AFTER;
  }

}
