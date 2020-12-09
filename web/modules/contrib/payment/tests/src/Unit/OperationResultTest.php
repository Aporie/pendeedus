<?php

namespace Drupal\Tests\payment\Unit;

use Drupal\payment\OperationResult;
use Drupal\payment\Response\ResponseInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\OperationResult
 *
 * @group Payment
 */
class OperationResultTest extends UnitTestCase {

  /**
   * The response.
   *
   * @var \Drupal\payment\Response\ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $response;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\OperationResult
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->response = $this->createMock(ResponseInterface::class);
  }

  /**
   * @covers ::isCompleted
   * @covers ::__construct
   */
  function testIsCompleted() {
    $this->sut = new OperationResult();
    $this->assertTrue($this->sut->isCompleted());

    $this->sut = new OperationResult($this->response);
    $this->assertFalse($this->sut->isCompleted());
  }

  /**
   * @covers ::getCompletionResponse
   * @covers ::__construct
   */
  function testGetCompletionResponse() {
    $this->sut = new OperationResult();
    $this->assertNULL($this->sut->getCompletionResponse());

    $this->sut = new OperationResult($this->response);
    $this->assertSame($this->response, $this->sut->getCompletionResponse());
  }

}
