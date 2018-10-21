<?php

namespace Drupal\palom_search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\palom_country\PalomCountry;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * @SearchApiProcessor(
 *   id = "yrv_country_value",
 *   label = @Translation("YRV Country Value"),
 *   description = @Translation("YRV Country Value"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *  hidden = true,
 *  locked = true,
 * )
 */

class CountryValue extends ProcessorPluginBase {

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
                'label' => $this->t('YRV Country Value'),
                'description' => $this->t('YRV Country Value'),
                'type' => 'string',
                'processor_id' => $this->getPluginId(),
            ];
            $properties['yrv_country_value'] = new ProcessorProperty($definition);
        }

        return $properties;
    }

    public function addFieldValues(ItemInterface $item){
        $entity = $item->getOriginalObject()->getValue();

        if ($entity instanceof EntityInterface && $entity->hasField('field_country')){
            $geo = $entity->get('field_country')->referencedEntities();
            if ($geo){

                foreach($geo as $geo_obj){
                    $geo_id = $geo_obj->id();

                    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'yrv_country_value');
                    if (PalomCountry::getGeoType($geo_id) == GEO_IS_COUNTRY){
                        foreach($fields as $field){
                            $geo_name = $geo_obj->getName();
                            $field->addValue($geo_name);
                        }
                    }
                    else if (PalomCountry::getGeoType($geo_id) == GEO_IS_REGION){

                        $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($geo_id);
                        $parent = reset($parent);

                        foreach($fields as $field){
                            $geo_name = $parent->getName();
                            $field->addValue($geo_name);
                        }
                    }
                }

            }
        }
    }
}