<?php

namespace Drupal\palom_search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\palom_libs\PalomLibs;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * @SearchApiProcessor(
 *   id = "yrv_city_value",
 *   label = @Translation("YRV City Value"),
 *   description = @Translation("YRV City Value"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *  hidden = true,
 *  locked = true,
 * )
 */

class CityValue extends ProcessorPluginBase {

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
                'label' => $this->t('YRV City Value'),
                'description' => $this->t('YRV City Value'),
                'type' => 'string',
                'processor_id' => $this->getPluginId(),
            ];
            $properties['yrv_city_value'] = new ProcessorProperty($definition);
        }

        return $properties;
    }

    public function addFieldValues(ItemInterface $item){
        $entity = $item->getOriginalObject()->getValue();

        if ($entity instanceof EntityInterface && $entity->hasField('field_city')){
            $geo = $entity->get('field_city')->referencedEntities();
            if ($geo){
                foreach($geo as $geo_obj){
                    $geo_id = $geo_obj->id();

                    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'yrv_city_value');
                    foreach($fields as $field){
                        $city_name = PalomLibs::getCityTypeAndName($geo_id);
//                        $city_name = $geo_obj->getTitle();
                        $field->addValue($city_name);
                    }
                }

            }
        }
    }
}