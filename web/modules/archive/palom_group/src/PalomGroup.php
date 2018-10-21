<?php

namespace Drupal\palom_group;

use Drupal\Core\Database\Database;
use Drupal\group\Entity\GroupType;

class PalomGroup {

    private static  $orgn = ['piligrimage_service', 'transport_service', 'feeding_service', 'housing_service'];

    // Является ли организацией данный тип ноды
    public static function typeIsOrgn($node_type){
        return in_array($node_type, self::$orgn);
    }

    // Получить тип группы по типу ноды
    public static function getGroupTypeByNodeType($node_type){

        $org_group = [
            'piligrimage_service' => 'group_ps',
            'transport_service' => 'group_transport',
            'feeding_service' => 'group_feeding',
            'housing_service' => 'group_housing',
            'advert' => 'group_ps',
        ];

        return $org_group[$node_type];
    }

    // Получить ID групп, в которых состоит данная сущность
    public static function getGroupIdByEntityId($group_type, $plugin_name, $entity_id){
        $type = GroupType::load($group_type)->getContentPlugin($plugin_name)->getContentTypeConfigId();

        $conn = Database::getConnection();
        $q = $conn->select('group_content_field_data', 'gc');
        $q->addField('gc', 'gid');
        $q->condition('type', $type);
        $q->condition('entity_id', $entity_id);

        $results = $q->execute()->fetchCol();

        return $results;
    }
}
