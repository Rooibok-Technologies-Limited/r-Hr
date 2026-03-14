<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\Queue;

/**
 * Unit tests for the Beanstalkd Queue library.
 *
 * These tests verify instantiation and graceful failure when Beanstalkd
 * is not available (expected in most CI/test environments).
 */
class QueueTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------
    //  Instantiation
    // ---------------------------------------------------------------

    public function testQueueCanBeInstantiated(): void
    {
        $queue = new Queue();

        $this->assertInstanceOf(Queue::class, $queue);
    }

    // ---------------------------------------------------------------
    //  Push — Graceful Failure
    // ---------------------------------------------------------------

    public function testPushReturnsFalseWhenNotConnected(): void
    {
        $queue = new Queue();

        // When Beanstalkd is not running, push should return false (not throw)
        if (!$queue->isConnected()) {
            $result = $queue->push('test-tube', ['action' => 'test']);
            $this->assertFalse($result, 'push() should return false when Beanstalkd is unavailable');
        } else {
            // Beanstalkd IS available — push should return an int (job ID) or false
            $result = $queue->push('test-tube', ['action' => 'unit_test']);
            $this->assertTrue(
                is_int($result) || $result === false,
                'push() should return int job ID or false'
            );
        }
    }

    // ---------------------------------------------------------------
    //  Connection Check
    // ---------------------------------------------------------------

    public function testIsConnectedReturnsBool(): void
    {
        $queue = new Queue();
        $this->assertIsBool($queue->isConnected());
    }

    // ---------------------------------------------------------------
    //  Reserve — Graceful Failure
    // ---------------------------------------------------------------

    public function testReserveReturnsNullWhenNotConnected(): void
    {
        $queue = new Queue();

        if (!$queue->isConnected()) {
            $result = $queue->reserve('test-tube', 0);
            $this->assertNull($result, 'reserve() should return null when Beanstalkd is unavailable');
        } else {
            $this->markTestSkipped('Beanstalkd is connected — skipping disconnected reserve test');
        }
    }

    // ---------------------------------------------------------------
    //  Stats — Graceful Failure
    // ---------------------------------------------------------------

    public function testStatsReturnsArrayWhenNotConnected(): void
    {
        $queue = new Queue();

        if (!$queue->isConnected()) {
            $result = $queue->stats();
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } else {
            $result = $queue->stats();
            $this->assertIsArray($result);
        }
    }
}
