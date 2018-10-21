<?php

use \Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function field_elements_field_views_data(FieldStorageConfigInterface $field_storage)
{
  module_load_include('inc', 'views', 'views.views');

  // Get the default data from the views module.
  $data = views_field_default_views_data($field_storage);

  $args = ['@field_name' => $field_storage->getName()];

  // Loop through all of the results and set our overrides.
  foreach ($data as $table_name => $table_data) {

    $field_coordinates_table_data = [];
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $target_entity_type = \Drupal::service('entity.manager')->getDefinition($field_storage->getTargetEntityTypeId());

    // Add boundary handlers.
    $data[$table_name][$args['@field_name'] . '_geo'] = [
      'group' => $target_entity_type->getLabel(),
      'title' => t('Country and region (@field_name)', $args),
      'title short' => t('Country and region (@field_name)', $args),
      'help' => '',
      'filter' => [
        'id' => 'palom_views_geo_filter',
        'is_grouped' => TRUE,
        'table' => $table_name,
        'entity_type' => $entity_type_id,
        'field_name' => $args['@field_name'] . '_country_region',
        'real field' => $args['@field_name'],
        'label' => t('Country and region of ', $args),
        'allow empty' => TRUE,
        'additional fields' => [
          $args['@field_name'] . '_country_id',
          $args['@field_name'] . '_region_id',
        ],
      ],
    ];
  }

  return $data;

}

/**
 * Implements hook_field_views_data_alter().
 */
function field_elements_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage)
{
  /*
  if ($field_storage->getType() == 'field_palom_geo') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'palom_views_geo_filter';
        }
      }
    }
  }*/
}
