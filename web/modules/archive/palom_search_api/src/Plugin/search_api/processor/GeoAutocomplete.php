<?php

namespace Drupal\palom_search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\palom_country\PalomCountry;
use Drupal\palom_libs\PalomLibs;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * @SearchApiProcessor(
 *   id = "yrv_geo_autocomplete",
 *   label = @Translation("YRV Geo Autocomplete"),
 *   description = @Translation("YRV Geo Autocomplete"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *  hidden = true,
 *  locked = true,
 * )
 */

class GeoAutocomplete extends ProcessorPluginBase {

    public static function supportsIndex(IndexInterface $index){
        foreach($index->getDatasources() as $datasource){
            if ($datasource->getEntityTypeId() == 'node'){
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getPropertyDefinitions(DatasourceInterface $datasource = NULL){
        $properties = [];

        if (!$datasource) {
            $definition = [
                'label' => $this->t('YRV Geo Autocomplete'),
                'description' => $this->t('YRV Geo Autocomplete'),
                'type' => 'string',
                'processor_id' => $this->getPluginId(),
            ];
            $properties['yrv_geo_autocomplete'] = new ProcessorProperty($definition);
        }

        return $properties;
    }

    public function addFieldValues(ItemInterface $item){
        $entity = $item->getOriginalObject()->getValue();

        if ($entity instanceof EntityInterface && $entity->hasField('field_city')){

            $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'yrv_geo_autocomplete');

            // Get the title

            // Get city
            $objects = $entity->get('field_city')->referencedEntities();
            if ($objects){
                foreach($objects as $obj){
                    $city_name = PalomLibs::getCityInfo($obj->id());
                    foreach($fields as $field){
                        $field->addValue($city_name);
                    }
                }
            }

            // Add a region if exists

            // Add A country
        }
    }
}