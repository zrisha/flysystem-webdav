<?php

/**
 * @file
 * Contains \Drupal\flysystem_webdav\Tests\ModuleInstallUninstallWebTest.
 */

namespace Drupal\flysystem_webdav\Tests;

use Drupal\flysystem\Tests\ModuleInstallUninstallWebTest as Base;

/**
 * Tests module installation and uninstallation.
 *
 * @group flysystem_webdav
 */
class ModuleInstallUninstallWebTest extends Base {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['flysystem_webdav'];

}
