<?php

namespace Drupal\palom_country;

use Drupal\Core\Database\Database;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermStorage;

define('GEO_IS_COUNTRY', 1);
define('GEO_IS_REGION', 2);

class PalomCountry {

    // A country list
    public static function getCountryList(){
        $countries = [];
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('countries',0,1);
        foreach($terms as $term){
            $countries[$term->tid] = $term->name;
        }
        return $countries;
    }

    // A regions list
    public static function getRegionList($country_id){
        $regions = [];
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('countries',$country_id,2);
        foreach($terms as $term){
            $regions[$term->tid] = $term->name;
        }
        return $regions;
    }

    // Get the geoobject type
    public static function getGeoType($geo_id){
        $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($geo_id);

        if ($parents == [])
            return GEO_IS_COUNTRY;
        else
            return GEO_IS_REGION;
    }

    // Get list of countries contains entities of definitely types
    public static function getCountryListContainNodeType($node_types = []){

        $countries = [];
        $conn = Database::getConnection();

        // Countries with regions
        $q1 = $conn->select('taxonomy_term_data', 'ttd');

        $q1->innerJoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid=ttd.tid');
        $q1->addField('ttfd', 'tid');
        $q1->addField('ttfd', 'name');
        $q1->condition('ttd.vid', 'countries');

        $q1->innerJoin('taxonomy_term_hierarchy', 'tth', 'ttfd.tid=tth.tid');
        $q1->condition('tth.parent', 0);

        $q1->innerJoin('taxonomy_term_hierarchy', 'tth2', 'ttfd.tid=tth2.parent');

        $q1->innerJoin('node__field_country', 'fc', 'tth2.tid=fc.field_country_target_id');
        $q1->innerJoin('node_field_data', 'nfd', 'fc.entity_id=nfd.nid');

        if ($node_types !=[])
            $q1->condition('nfd.type', $node_types, 'IN');
        $q1->condition('nfd.status', 1);

        // Countries without regions
        $q2 = $conn->select('taxonomy_term_data', 'ttd');

        $q2->innerJoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid=ttd.tid');
        $q2->addField('ttfd', 'tid');
        $q2->addField('ttfd', 'name');
        $q2->condition('ttd.vid', 'countries');

        $q2->innerJoin('taxonomy_term_hierarchy', 'tth', 'ttfd.tid=tth.tid');
        $q2->condition('tth.parent', 0);

        $q2->innerJoin('node__field_country', 'fc', 'ttfd.tid=fc.field_country_target_id');
        $q2->innerJoin('node_field_data', 'nfd', 'fc.entity_id=nfd.nid');

        if ($node_types !=[])
            $q2->condition('nfd.type', $node_types, 'IN');
        $q2->condition('nfd.status', 1);

        $q = $q1->union($q2);
        $q->distinct();
        $q->orderBy('name');

        $result = $q->execute();

        foreach($result as $row){
            $countries[$row->tid] = $row->name;
        }

        return $countries;
    }

    // A list of regions contains entities of certain types
    public static function getRegionListContainNodeTypes($country_id, $node_types = []){
        $regions = [];
        $conn = Database::getConnection();

        $q = $conn->select('taxonomy_term_data', 'ttd');

        $q->innerJoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid=ttd.tid');
        $q->addField('ttfd', 'tid');
        $q->addField('ttfd', 'name');
        $q->condition('ttd.vid', 'countries');

        $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'ttfd.tid=tth.tid');
        $q->condition('tth.parent', $country_id);

        $q->innerJoin('node__field_country', 'fc', 'ttfd.tid=fc.field_country_target_id');
        $q->innerJoin('node_field_data', 'nfd', 'fc.entity_id=nfd.nid');

        if ($node_types !=[])
            $q->condition('nfd.type', $node_types, 'IN');
        $q->condition('nfd.status', 1);
        $q->distinct();

        $result = $q->execute();

        foreach($result as $row){
            $regions[$row->tid] = $row->name;
        }

        return $regions;
    }
}