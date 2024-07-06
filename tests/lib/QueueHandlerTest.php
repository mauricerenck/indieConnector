<?php

use Kirby\Content\Content;
use Kirby\Cms\Collection;
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
        $contentMock = new Content([
            'sourceUrl' => 'https://source.tld',
            'targetUrl' => 'https://target.tld',
            'queueStatus' => 'queued',
            'id' => '123',
            'retries' => 0,
        ]);
        $dataCollection = new Collection([$contentMock]);

        $this->webmentionReceiverMock->shouldReceive('processWebmention')->andReturn([
            'status' => 'success',
            'message' => 'webmention processed',
        ]);

        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with(
                'queue',
                ['id', 'sourceUrl', 'targetUrl', 'retries'],
                'WHERE queueStatus = "queued" OR queueStatus = "error"'
            )
            ->once()
            ->andReturn($dataCollection);

        $this->databaseMock
            ->shouldReceive('update')
            ->with('queue', ['queueStatus', 'processLog'], ['failed', 'max retries reached'], 'WHERE id = "123"')
            ->once()
            ->andReturn($dataCollection);
        $this->databaseMock->shouldReceive('delete')->with('queue', 'WHERE id = "123"')->once()->andReturn(true);

        $expected = [
            'status' => 'success',
            'message' => 'webmention processed',
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

        $contentMock = new Content([
            'sourceUrl' => 'https://source.tld',
            'targetUrl' => 'https://target.tld',
            'type' => 'webmention',
            'status' => 'queued',
            'created' => date('Y-m-d H:i:s'),
            'id' => '123',
            'retries' => 1,
        ]);
        $dataCollection = new Collection([$contentMock]);

        $this->webmentionReceiverMock->shouldReceive('processWebmention')->andReturn([
            'status' => 'error',
            'message' => 'webmention processing error',
        ]);

        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with(
                'queue',
                ['id', 'sourceUrl', 'targetUrl', 'retries'],
                'WHERE queueStatus = "queued" OR queueStatus = "error"'
            )
            ->once()
            ->andReturn($dataCollection);

        $this->databaseMock
            ->shouldReceive('update')
            ->with(
                'queue',
                ['queueStatus', 'processLog', 'retries'],
                ['error', 'webmention processing error', 2],
                'WHERE id = "123"'
            )
            ->once()
            ->andReturn(true);

        $expected = [
            'status' => 'error',
            'message' => 'webmention processing error',
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

        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('select')
            ->with(
                'queue',
                ['id', 'sourceUrl', 'targetUrl', 'retries'],
                'WHERE queueStatus = "queued" OR queueStatus = "error"'
            )
            ->once();

        $result = $queueHandler->processQueue();

        $this->assertNull($result);
    }
}
