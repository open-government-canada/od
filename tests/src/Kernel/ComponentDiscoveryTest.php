<?php

namespace Drupal\Tests\od\Kernel;

use Drupal\Core\Extension\Extension;
use Drupal\KernelTests\KernelTestBase;
use Drupal\wxt\ComponentDiscovery;

/**
 * @group od
 *
 * @coversDefaultClass \Drupal\od\ComponentDiscovery
 */
class ComponentDiscoveryTest extends KernelTestBase {

  /**
   * The ComponentDiscovery under test.
   *
   * @var \Drupal\od\ComponentDiscovery
   */
  protected $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->discovery = new ComponentDiscovery(
      $this->container->get('app.root')
    );
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $components = $this->discovery->getAll();

    $this->assertInstanceOf(Extension::class, $components['od_core']);
    $this->assertInstanceOf(Extension::class, $components['od_ext']);
  }

  /**
   * @covers ::getMainComponents
   */
  public function testGetMainComponents() {
    $components = $this->discovery->getMainComponents();

    $this->assertInstanceOf(Extension::class, $components['od_core']);
  }

  /**
   * @covers ::getSubComponents
   */
  public function testGetSubComponents() {
    $components = $this->discovery->getSubComponents();

    $this->assertArrayNotHasKey('od_core', $components);
  }

}
