<?php

/**
 * @file
 * Contains \Drupal\Tests\dfp\Unit\DfpHtmlResponseAttachmentsProcessorTest.
 */

namespace Drupal\Tests\dfp\Unit;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\dfp\DfpHtmlResponseAttachmentsProcessor;
use Drupal\dfp\TokenInterface;
use Drupal\dfp\View\TagView;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\dfp\DfpHtmlResponseAttachmentsProcessor
 * @group dfp
 */
class DfpHtmlResponseAttachmentsProcessorTest extends UnitTestCase {

  /**
   * A mock core html attachment processor.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $attachmentProcessor;

  /**
   * A mock DFP token service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $token;

  /**
   * A mock asset resolver service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $assetResolver;

  /**
   * A mock CSS collection renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $cssCollectionRenderer;

  /**
   * A mock JS collection renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $jsCollectionRenderer;

  /**
   * A mock RequestStack.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $requestStack;

  /**
   * A mock renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $renderer;

  /**
   * A mock module handler.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Mock core attachment processor.
    $this->attachmentProcessor = $this->getMockBuilder(AttachmentsResponseProcessorInterface::class)->disableOriginalConstructor()->getMock();
    $this->attachmentProcessor->method('processAttachments')
      ->willReturnArgument(0);

    $this->token = $this->prophesize(TokenInterface::class);
    $this->assetResolver = $this->prophesize(AssetResolverInterface::class);
    $this->cssCollectionRenderer = $this->prophesize(AssetCollectionRendererInterface::class);
    $this->jsCollectionRenderer = $this->prophesize(AssetCollectionRendererInterface::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->renderer = $this->prophesize(RendererInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
  }

  /**
   * @covers ::processAttachments
   */
  public function testProcessAttachments() {
    // Create a response with two dfp_slot attachments and ensure that the
    // they are converted to html_head attachments. Also ensure that the other
    // html_head elements are added to create the necessary javascript in the
    // right order.
    $response = new HtmlResponse();
    for ($i = 1; $i < 3; $i++) {
      $tag = $this->prophesize(TagView::class);
      $tag->id()->willReturn($i);
      $attachments['dfp_slot'][] = $tag->reveal();
    }
    $response->setAttachments($attachments);
    $config_factory = $this->getConfigFactoryStub(['dfp.settings' => ['targeting' => []]]);

    $response = $this->getDfpAttachmentProcessor($config_factory)->processAttachments($response);
    $this->assertEquals('dfp-js-head-top', $response->getAttachments()['html_head'][0][1]);
    $this->assertEquals('dfp-slot-definition-1', $response->getAttachments()['html_head'][1][1]);
    $this->assertEquals('dfp-slot-definition-2', $response->getAttachments()['html_head'][2][1]);
    $this->assertEquals('dfp-js-head-bottom', $response->getAttachments()['html_head'][3][1]);
    $this->assertArrayNotHasKey('dfp_slot', $response->getAttachments(), 'The dfp_slot attachments are converted to html_head attachments.');
  }

  /**
   * @covers ::processAttachments
   */
  public function testProcessAttachmentsNoSlots() {
    // Ensure that if there are no slots nothing is added to the attachments.
    $response = new HtmlResponse();
    $config_factory = $this->getConfigFactoryStub();
    $response = $this->getDfpAttachmentProcessor($config_factory)->processAttachments($response);
    $this->assertEmpty($response->getAttachments());
  }

  /**
   * Creates a DfpHtmlResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A mock config factory that can contain 'dfp.settings' configuration.
   *
   * @return \Drupal\dfp\DfpHtmlResponseAttachmentsProcessor
   *   The DfpHtmlResponseAttachmentsProcessor object.
   */
  protected function getDfpAttachmentProcessor(ConfigFactoryInterface $config_factory) {
    return new DfpHtmlResponseAttachmentsProcessor(
      $this->attachmentProcessor,
      $this->token->reveal(),
      $this->assetResolver->reveal(),
      $config_factory,
      $this->cssCollectionRenderer->reveal(),
      $this->jsCollectionRenderer->reveal(),
      $this->requestStack->reveal(),
      $this->renderer->reveal(),
      $this->moduleHandler->reveal()
    );
  }

}
