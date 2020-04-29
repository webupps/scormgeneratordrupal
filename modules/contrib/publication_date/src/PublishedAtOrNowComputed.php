<?php

namespace Drupal\publication_date;

use Drupal\Core\TypedData\TypedData;

class PublishedAtOrNowComputed extends TypedData {

  public function getValue() {
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $value = $item->{($this->definition->getSetting('source'))};
    if ($value && $value != PUBLICATION_DATE_DEFAULT) {
      return $value;
    }
    return REQUEST_TIME;
  }

}
