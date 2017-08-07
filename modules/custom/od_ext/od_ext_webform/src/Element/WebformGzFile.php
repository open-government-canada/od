<?php

namespace Drupal\od_ext_webform\Element;

use Drupal\webform\Element\WebformManagedFileBase;

/**
 * Provides a webform element for an 'tar_file' element.
 *
 * @FormElement("webform_tar_file")
 */
class WebformGzFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'tar/*';

}
