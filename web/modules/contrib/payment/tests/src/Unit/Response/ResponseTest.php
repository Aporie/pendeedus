<?php

namespace Drupal\Tests\payment\Unit\Response;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\payment\Response\Response;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @coversDefaultClass \Drupal\payment\Response\Response
 *
 * @group Payment
 */
class ResponseTest extends UnitTestCase {

  /**
   * The redirect URL.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * The response.
   *
   * @var \Symfony\Component\HttpFoundation\Response|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $symfonyResponse;

  /**
   * The route name to test with.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Response\Response
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->routeName = $this->randomMachineName();

    $this->redirectUrl = new Url($this->routeName);

    $this->symfonyResponse = $this->getMockBuilder(SymfonyResponse::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->sut = new Response($this->redirectUrl, $this->symfonyResponse);
  }

  /**
   * @covers ::getRedirectUrl
   */
  function testGetRedirectUrl() {
    $this->assertSame($this->redirectUrl, $this->sut->getRedirectUrl());
  }

  /**
   * @covers ::getResponse
   */
  function testGetResponse() {
    $this->assertSame($this->symfonyResponse, $this->sut->getResponse());
  }

  /**
   * @covers ::getResponse
   */
  function testGetResponseWithoutResponse() {
    $generated_url = new GeneratedUrl();
    $generated_url->setGeneratedUrl($this->randomMachineName());
    $generated_url->addCacheTags(['node:1']);
    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator->expects($this->atLeastOnce())
      ->method('generateFromRoute')
      ->with($this->routeName)
      ->willReturn($generated_url);

    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator);

    \Drupal::setContainer($container);

    $this->sut = new Response($this->redirectUrl);

    $response = $this->sut->getResponse();
    $this->assertInstanceOf(TrustedRedirectResponse::class, $response);
    $this->assertEquals(['node:1'], $response->getCacheableMetadata()->getCacheTags());
  }

}
