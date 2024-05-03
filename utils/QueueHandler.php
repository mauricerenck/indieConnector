<?php
namespace mauricerenck\IndieConnector;

use Exception;

class QueueHandler
{
    private $indieDb;
    private $receiver;

    public function __construct(
        private ?bool $queueEnabled = null,
        private ?IndieConnectorDatabase $indieDatabase = null,
        private ?WebmentionReceiver $webmentionReceiver = null
    ) {
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->receiver = $webmentionReceiver ?? new WebmentionReceiver();
        $this->queueEnabled = $queueEnabled ?? option('mauricerenck.indieConnector.queue.enabled', false);
    }

    public function queueEnabled(): bool
    {
        return $this->queueEnabled;
    }

    public function queueWebmention(string $sourceUrl, string $targetUrl)
    {
        $mentionDate = $this->indieDb->getFormattedDate();

        try {
            $uniqueHash = md5($targetUrl . $sourceUrl . $mentionDate);
            $this->indieDb->insert(
                'queue',
                ['id', 'sourceUrl', 'targetUrl', 'queueStatus'],
                [$uniqueHash, $sourceUrl, $targetUrl, 'queued']
            );
        } catch (Exception $e) {
            echo 'Could not queue webmention: ', $e->getMessage(), "\n";
            return;
        }
    }

    public function processQueue(): array
    {
        $queue = $this->indieDb->select('queue', ['id', 'sourceUrl', 'targetUrl'], 'WHERE queueStatus = "queued"');

        if (empty($queue)) {
            return [
                'status' => 'success',
                'message' => 'no queued webmentions',
                'queueId' => null,
            ];
        }

        foreach ($queue as $mention) {
            $sourceUrl = $mention['sourceUrl'];
            $targetUrl = $mention['targetUrl'];

            $result = $this->receiver->processWebmention($sourceUrl, $targetUrl);

            switch ($result['status']) {
                case 'success':
                    $this->indieDb->update(
                        'queue',
                        ['queueStatus'],
                        ['processed'],
                        'WHERE id = "' . $mention['id'] . '"'
                    );

                    return [
                        'status' => 'success',
                        'message' => 'webmention processed',
                        'queueId' => $mention['id'],
                    ];

                case 'error':
                    $this->indieDb->update(
                        'queue',
                        ['queueStatus', 'processLog'],
                        ['error', $result['message']],
                        'WHERE id = "' . $mention['id'] . '"'
                    );

                    return [
                        'status' => 'error',
                        'message' => 'webmention processing error',
                        'queueId' => $mention['id'],
                    ];
            }
        }
    }
}
