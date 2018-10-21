<?php

namespace Drupal\palom_advert;

use Drupal\node\Entity\Node;

/**
 * Получить тип дат для поездки
 * @param $advert_id
 */

class PalomAdvert {

    // Получить типа поездки в зависимости от даты
    public static function GetAdvertDateType($advert_id){
        $advert = Node::load($advert_id);
        $values = $advert->get('field_advert_dates')->getValue();
        if ((sizeof($values) == 1) && ($values[0]['value'] == '2500-01-01'))
            return 1;
        if ((sizeof($values) == 1) && ($values[0]['value'] == '2600-01-01'))
            return 2;
        return 0;
    }
}

