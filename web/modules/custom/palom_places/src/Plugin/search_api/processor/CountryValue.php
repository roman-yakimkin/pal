<?php

namespace Drupal\palom_places\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the country_value.
 *
 * @SearchApiProcessor(
 *   id = "country_value",
 *   label = @Translation("Country_value"),
 *   description = @Translation("Adds the country vaue to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class CountryValue extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Country Value'),
        'description' => $this->t('A country value for element'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['country_value'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
    $title = $item->getOriginalObject()->getValue('title');

    if ($title) {
      $url = $url->toString();
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'country_value');
      foreach ($fields as $field) {
        $field->addValue($title);
      }
    }
  }

}
