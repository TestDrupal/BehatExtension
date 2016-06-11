<?php
namespace TestDrupal\BehatExtension\Context;

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


/**
 * Defines application features from the specific context.
 */
class TestEntityManager {

  // Store entities as EntityMetadataEntitys for easy property inspection.
  //protected $entities = array();

  protected $entity_type = '';
  protected $bundle = '';
  protected $bundle_key = FALSE;
  protected $field_map = array();
  protected $field_properties = array();
  protected $behatContext = array();

  /**
   * RawTestDrupalEntityContext constructor.
   * @param $entity_type
   * @param $bundle
   * @param array $field_map_overrides
   */
  public function __construct($entity_type , $bundle, $field_map_overrides = array('published' => 'status')) {
    // Super helpful list of the methods from the now deprecated
    // EntityManager https://www.drupal.org/node/2549139

    $entityManager = \Drupal::entityTypeManager();
    $entityBundleInfo = \Drupal::getContainer()->get('entity_type.bundle.info')->getBundleInfo($entity_type);
    $entityInfo = $entityManager->getDefinition($entity_type);
    $fieldManager = \Drupal::getContainer()->get('entity_field.manager');

    $this->entity_type = $entity_type;
    $this->field_properties = array();
    $this->bundle_key = $entityInfo->getKey('bundle');

    // Check that the bundle specified actually exists, or if none given,
    // that this is an entity with no bundles (single bundle w/ name of entity)
    if (!isset( $entityBundleInfo[$bundle])) {
      throw new \Exception("Bundle $bundle doesn't exist for entity type $this->entity_type.");
    }
    else {
      $this->bundle = $bundle;
    }

    // Store the field properties for later.
    $field_info = $fieldManager->getFieldDefinitions($this->entity_type, $this->bundle);

    // Collect the default and overridden field mappings.
    foreach ($field_info as $field => $info) {
      // First check if this field mapping is overridden.
      if ($label = array_search($field, $field_map_overrides)) {
        $this->field_map[$label] = $field;
      }
      // Use the default label;
      else {
        $label = $info->getLabel();
        $this->field_map[strtolower($label)] = $field;
      }
    }
  }

  /**
   * @AfterScenario
   *
   * @param AfterScenarioScope $scope
   */
  public function deleteAll(AfterScenarioScope $scope) {
    $delete_entities = $this->getBehatContext()->getEntityStore()->retrieve($this->entity_type, $this->bundle);
    $entity_storage = \Drupal::entityTypeManager()
      ->getStorage($this->entity_type);
    if ($delete_entities === false) {
      return;
    }

    $final_delete_list = array();
    foreach ($delete_entities as $entity) {
      // The behat user teardown deletes all the content of a user automatically,
      // so we want to get a fresh entity instead of relying on the entity
      // (or a bool that confirms it's deleted)


      $entity = $entity_storage->load($entity->id());

      if ($entity !== NULL) {
        $final_delete_list[] = $entity;
      }
    }
    if (!empty($final_delete_list)){
      $entity_storage->delete($final_delete_list);
    }

    // For Scenarios Outlines, EntityContext is not deleted and recreated
    // and thus the entities array is not deleted and houses stale entities
    // from previous examples, so we clear it here
    $this->getBehatContext()->getEntityStore()->delete($this->entity_type, $this->bundle);
    $this->getBehatContext()->getEntityStore()->names_flush();
  }

  /**
   * Get Entity by name
   *
   * @param $name
   * @return EntityDrupalEntity or FALSE
   */
  public function getByName($name) {
    return $this->getBehatContext()->getEntityStore()->retrieve_by_name($name);
  }

  /**
   * Explode a comma separated string in a standard way.
   *
   */
  function explode_list($string) {
    $array = explode(',', $string);
    $array = array_map('trim', $array);
    return is_array($array) ? $array : array();
  }

  /**
   * @param EntityDrupalEntity $entity
   * @param array $field
   * @return mixed
   * @throws \Exception
   */
  public function apply_fields($entity, $fields) {
    foreach ($fields as $label => $value ) {
      if(isset($this->field_map[$label]) && $this->field_map[$label] === 'status'){
        $value = $this->convertStringToBool($value);
      }
      $this->set_field($entity, $label, $value);
    }
    return $entity;
  }

  /**
   * @param EntityDrupalEntity $entity
   * @param $label
   * @param $value
   * @throws \Exception
   */
  public function set_field($entity, $label, $value) {
    $property = null;
    try {
      // Make sure there is a mapping to an actual property.
      if (!isset($this->field_map[$label])) {
        $all_fields = implode(", \n", array_keys($this->field_map));
        throw new \Exception("There is no field mapped to label '$label'. Available fields are: $all_fields");
      }
      $field_name = $this->field_map[$label];

      // If no type is set for this property, then try to just output as-is.
      $fieldManager = \Drupal::getContainer()->get('entity_field.manager');
      $field_info = $fieldManager->getFieldDefinitions($this->entity_type, $this->bundle);


      if (!isset($field_info[$field_name])) {
        $entity->$field_name = $value;
        return;
      }

      $field = $field_info[$field_name];
      $field_type = $field->getType();
      # getCardinality not avaiable for the subject field of forums.
      if (method_exists($field_info[$field_name], 'getCardinality') && $field_info[$field_name]->getCardinality() > 1) {
        $field_type = $field_info[$field_name]->getItemDefinition()->getDataType();
        $values = $this->explode_list($value);
      }
      else {
        $values = array($value);
      }

      foreach ($values as $val) {
        if (empty($val)) {
          continue;
        }
        // See the full list of Drupal 8 field types.
        // https://www.drupal.org/node/2078241#field-types
        // Note that custom types are possible.
        switch ($field_type) {
          case 'string':
          case 'email':

            $entity->$field_name->value = $val;
            break;
          case 'entity_reference':
            $found_entity = $this->getBehatContext()->getEntityStore()->retrieve_by_name($val);
            if ($found_entity !== FALSE) {
              $entity->$field_name->entity = $found_entity;
            }
            else {
              throw new \Exception("Named Entity '$val' not found, was it created during the test?");
            }
            break;

          // Can be NID
          case 'integer':
            $entity->$field_name->set((int) $value);
            break;

          // Do our best to handle 0, false, "false", or "No"
          case 'boolean':
            if (gettype($value) == 'string') {
              $value = $this->convertStringToBool($value);
            }
            $entity->$property->set((bool) $value);
            break;

          // Dates - handle strings as best we can. See http://php.net/manual/en/datetime.formats.relative.php
          case 'date':
            $timestamp = strtotime($value);
            if ($timestamp === FALSE) {
              throw new \Exception("Couldn't create a date with '$value'");
            }
            $entity->$property->set($timestamp);
            break;

          // User reference
          case 'user':
            $user = user_load_by_name($value);
            if ($user === FALSE) {
              throw new \Exception("Can't find a user with username '$value'");
            }
            $entity->$property->set($user);
            break;

          // Simple text field.
          case 'text':
            $entity->$property->set($value);
            break;

          // Formatted text like body
          case 'text_formatted':
            // For now just apply the value directly.
            $entity->$property->set(array('value' => $value));
            break;

          case 'taxonomy_term':
            if (!isset($value)) {
              break;
            }
            if ($found_term = $this->tidFromTermName($property, $value)) {
              $tid = $found_term;
            }
            else {
              throw new \Exception("Term '$value'' not found for field '$property'");
            }
            $entity->$property->set($tid);
            break;


          case "list<taxonomy_term>":
            // Convert the tags to tids.
            $tids = array();
            foreach ($this->explode_list($value) as $term) {
              if ($found_term = $this->tidFromTermName($property, $term)) {
                $tids[] = $found_term;
              }
              else {
                throw new \Exception("Term '$term'' not found for field '$property'");
              }
            }
            $entity->$property->set($tids);
            break;

          /* TODO BELOW */

          // Node reference.
          case 'node':
          case 'list<node>':
            $nids = array();
            foreach ($this->explode_list($value) as $name) {
              if (empty($name)) {
                continue;
              }
              $found_node_entity = $this->getBehatContext()->getEntityStore()->retrieve_by_name($name);
              if ($found_node_entity !== FALSE) {
                $nids[] = $found_node_entity->nid->value();
              }
              else {
                throw new \Exception("Named Node '$name' not found, was it created during the test?");
              }
            }
            $entity->$property->set($nids);
            break;


            break;
          // Not sure (something more complex)
          case 'struct':
            // Images
          case 'field_item_image':
            // Links
          case 'field_item_link':
            // Text field formatting?
          case 'token':
            // References to nodes
          default:
            // For now, just error out as we can't handle it yet.
            throw new \Exception("Not sure how to handle field '$label' with type '$field_type'");
            break;
        }
      }
    }
    catch (EntityMetadataEntityException $e ) {
      $print_val = print_r($value, true);
      throw new \Exception("Error when setting field '$property' with value '$print_val': Error Message => {$e->getMessage()}");
    }
  }

  /**
   * Creates entities from a given table.
   *
   * Builds key-mapped arrays from a TableNode matching this context's field map,
   * then cycles through each array to start the entity build routine for each
   * corresponding array. This function will be called by sub-contexts to generate
   * their entities.
   *
   * @param TableNode $entityTable - provided
   * @throws \Exception
   */
  public function addMultipleFromTable(TableNode $entityTable) {
    foreach($this->arrayFromTableNode($entityTable) as $entity) {
      $this->save($entity);
    }
  }

  /**
   * Build routine for an entity.
   *
   * @param $fields - the array of key-mapped values
   * @return EntityDrupalEntity $entity - EntityMetadataEntity
   */
  public function save($fields) {
    $storage = \Drupal::entityTypeManager()->getStorage($this->entity_type);
    $entity = $storage->create(array(
      $this->bundle_key => $this->bundle,
    ));
    $this->pre_save($entity, $fields);
    $entity->save();
    $this->post_save($entity, $fields);
    return $entity;
  }

   /**
    * Do further processing after saving.
    *
    * @param EntityDrupalEntity $entity
    * @param $fields
    */
  public function pre_save($entity, $fields) {

    // Update the changed date after the entity has been saved.
    if (isset($fields['date changed'])) {
      unset($fields['date changed']);
    }
    if (!isset($fields['author']) && isset($this->field_map['author'])) {
      $field = $this->field_map['author'];
      $user = $this->getCurrentUser();
      if ($user) {
        $entity->$field->set($user);
      }
    }
    //$this->dispatchTestDrupalHooks('BeforeTestDrupalEntityCreateScope', $entity, $fields);
    $this->apply_fields($entity, $fields);
  }

  /**
   * Do further processing after saving.
   *
   * @param EntityDrupalEntity $entity
   * @param array $fields
   */
  public function post_save($entity, $fields) {
    //$this->dispatchTestDrupalHooks('AfterTestDrupalEntityCreateScope', $entity, $fields);
    // Remove the base url from the url and add it
    // to the page array for easy navigation.
    $url = $entity->toUrl()->toString();
    // Add the url to the page array for easy navigation.
    $page = new Page($entity->label(), $url);
    $this->getBehatContext()->getPageStore()->store($page);

    if (isset($fields['date changed'])) {
      $this->setChangedDate($entity, $fields['date changed']);
    }

    // Add the created entity to the array so it can be deleted later.
    $this->getBehatContext()->getEntityStore()->store($this->entity_type, $this->bundle, $entity->id(), $entity, $entity->label());
  }

  /**
   * Converts a TableNode into an array.
   *
   * Takes an TableNode and builds a multi-dimensional array,
   *
   * @param TableNode
   * @throws \Exception
   * @returns array()
   */
  function arrayFromTableNode(TableNode $itemsTable) {
    $items = array();
    foreach ($itemsTable as $itemHash) {
      $items[] = $itemHash;
    }
    return $items;
  }

  /**
   * Converts a string value to a boolean value
   *
   * @param $value String
   */
  function convertStringToBool($value){
    $value = strtolower($value);
    $value = ($value === 'yes') ? TRUE : $value;
    $value = ($value === 'no') ? FALSE : $value;
    return $value;
  }

  function tidFromTermName($field_name, $term) {
    $info = field_info_field($field_name);
    $vocab_machine_name = $info['settings']['allowed_values'][0]['vocabulary'];
    if ($found_terms = taxonomy_get_term_by_name($term, $vocab_machine_name)) {
      $found_term = reset($found_terms);
      return $found_term->tid;
    }
    else {
      return false;
    }
  }

  /**
   * Forces the change of a entities changed date as drupal makes this difficult.
   *
   * Note that this is only supported for nodes currently. TODO Support all entities.
   * Also, there is no guarantee that another action won't cause the updated date to change.
   *
   * @param EntityDrupalEntity $saved_entity
   * @param String $time_str See time formats supported by strtotime().
   */
  function setChangedDate($saved_entity, $time_str) {
    if (! ($saved_entity->type() == 'node')) {
      throw new Exception("Specifying the 'changed' date is only supported for nodes currently.");
    }
    if (!$nid = $saved_entity->getIdentifier()) {
      throw new Exception("Node ID could not be found. A node must be saved first before the changed date can be updated.");
    }
    // Use REQUEST_TIME, because that will remain consistent across all tests.
    if (!$timestamp = strtotime($time_str, REQUEST_TIME)) {
      throw new Exception("Could not create a timestamp from $time_str.");
    }
    else {
      db_update('node')
        ->fields(array('changed' => $timestamp))
        ->condition('nid', $nid, '=')
        ->execute();

      db_update('node_revision')
        ->fields(array('timestamp' => $timestamp))
        ->condition('nid', $nid, '=')
        ->execute();
    }
  }
  /*
   * Fire off a TestDrupal hook.
   *
   * Based on RawDrupalContext::dispatchHooks().
   *
   * @param $scopeType
   * @param \stdClass $entity
   * @throws
   */
  /*protected function dispatchTestDrupalHooks($scopeType, EntityInterface $entity, &$fields) {
    $fullScopeClass = 'TestDrupal\\BehatExtension\\Hook\\Scope\\' . $scopeType;
    $scope = new $fullScopeClass($this->getDrupal()->getEnvironment(), $this, $entity, $fields);
    $callResults = $this->dispatcher->dispatchScopeHooks($scope);

    // The dispatcher suppresses exceptions, throw them here if there are any.
    foreach ($callResults as $result) {
      if ($result->hasException()) {
        $exception = $result->getException();
        throw $exception;
      }
    }
  } */

  public function setBehatContext($behatContext) {
    $this->behatContext = $behatContext;
  }

  public function getBehatContext() {
    return $this->behatContext;
  }
}


