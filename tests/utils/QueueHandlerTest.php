<?php

use mauricerenck\IndieConnector\QueueHandler;
use mauricerenck\IndieConnector\TestCaseMocked;

final class QueueHandlerTest extends TestCaseMocked
{
    private $databaseMock;
    private $webmentionReceiverMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseMock = Mockery::mock(mauricerenck\IndieConnector\IndieConnectorDatabase::class);
        $this->webmentionReceiverMock = Mockery::mock(mauricerenck\IndieConnector\WebmentionReceiver::class);
    }

    /**
     * @group queueHandler
     * @testdox processQueue - should process queue entries
     */
    public function testProcessQueue()
    {
        $queueHandler = new QueueHandler(true, $this->databaseMock, $this->webmentionReceiverMock);

        $queueMock = [
            [
                'sourceUrl' => 'https://source.tld',
                'targetUrl' => 'https://target.tld',
                'type' => 'webmention',
                'status' => 'queued',
                'created' => date('Y-m-d H:i:s'),
                'id' => '123',
            ],
        ];

        $this->webmentionReceiverMock->shouldReceive('processWebmention')->andReturn([
            'status' => 'success',
            'message' => 'webmention processed',
        ]);

        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with('queue', ['id', 'sourceUrl', 'targetUrl'], 'WHERE queueStatus = "queued"')
            ->once()
            ->andReturn($queueMock);
        $this->databaseMock
            ->shouldReceive('update')
            ->with('queue', ['queueStatus'], ['processed'], 'WHERE id = "123"')
            ->once()
            ->andReturn(true);

        $expected = [
            'status' => 'success',
            'message' => 'webmention processed',
            'queueId' => '123',
        ];

        $result = $queueHandler->processQueue();

        $this->assertEquals($expected, $result);
    }

    /**
     * @group queueHandler
     * @testdox processQueue - should handle error
     */
    public function testProcessQueueFailed()
    {
        $queueHandler = new QueueHandler(true, $this->databaseMock, $this->webmentionReceiverMock);

        $queueMock = [
            [
                'sourceUrl' => 'https://source.tld',
                'targetUrl' => 'https://target.tld',
                'type' => 'webmention',
                'status' => 'queued',
                'created' => date('Y-m-d H:i:s'),
                'id' => '123',
            ],
        ];

        $this->webmentionReceiverMock->shouldReceive('processWebmention')->andReturn([
            'status' => 'error',
            'message' => 'something went wrong',
        ]);

        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with('queue', ['id', 'sourceUrl', 'targetUrl'], 'WHERE queueStatus = "queued"')
            ->once()
            ->andReturn($queueMock);
        $this->databaseMock
            ->shouldReceive('update')
            ->with('queue', ['queueStatus', 'processLog'], ['error', 'something went wrong'], 'WHERE id = "123"')
            ->once()
            ->andReturn(true);

        $expected = [
            'status' => 'error',
            'message' => 'webmention processing error',
            'queueId' => '123',
        ];

        $result = $queueHandler->processQueue();

        $this->assertEquals($expected, $result);
    }

    /**
     * @group queueHandler
     * @testdox processQueue - should handle empty queue
     */
    public function testProcessQueueEmpty()
    {
        $queueHandler = new QueueHandler(true, $this->databaseMock, $this->webmentionReceiverMock);

        $queueMock = [];
        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with('queue', ['id', 'sourceUrl', 'targetUrl'], 'WHERE queueStatus = "queued"')
            ->once()
            ->andReturn($queueMock);

        $expected = [
            'status' => 'success',
            'message' => 'no queued webmentions',
            'queueId' => null,
        ];

        $result = $queueHandler->processQueue();

        $this->assertEquals($expected, $result);
    }
}
