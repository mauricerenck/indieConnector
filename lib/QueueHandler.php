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
        private ?WebmentionReceiver $webmentionReceiver = null,
        private ?int $retries = null
    ) {
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->receiver = $webmentionReceiver ?? new WebmentionReceiver();
        $this->queueEnabled = $queueEnabled ?? option('mauricerenck.indieConnector.queue.enabled', false);
        $this->retries = $retries ?? option('mauricerenck.indieConnector.queue.retries', 5);
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

    public function processQueue(int $limit = 0)
    {
        $queue = $this->getQueuedItems($limit);

        if (empty($queue)) {
            return;
        }

        $processedItems = [];
        foreach ($queue as $mention) {
            $processedItems[] = $this->processQueueItem($mention);
        }

        return $processedItems;
    }

    public function getQueuedItems(int $limit = 0, bool $includeFailed = false)
    {
        $limitQuery = $limit > 0 ? ' LIMIT ' . $limit : '';
        $failedQuery = $includeFailed ? ' OR queueStatus = "failed"' : '';
        return $this->indieDb->select(
            'queue',
            ['id', 'sourceUrl', 'targetUrl', 'retries', 'queueStatus', 'processLog'],
            'WHERE queueStatus = "queued" OR queueStatus = "error"' . $failedQuery . $limitQuery
        );
    }

    public function getAndProcessQueuedItem(string $id)
    {
        $mention = $this->indieDb->select(
            'queue',
            ['id', 'sourceUrl', 'targetUrl', 'retries', 'queueStatus'],
            'WHERE id = "' . $id . '"'
        )->first();

        if (empty($mention)) {
            return ['id' => $id, 'queueStatus' => 'confusion', 'processLog' => 'Entry not found', 'retries' => 0];
        }

        return $this->processQueueItem($mention);
    }

    public function processQueueItem($mention)
    {
        $sourceUrl = $mention->sourceUrl();
        $targetUrl = $mention->targetUrl();
        $mentionId = $mention->id();
        $retries =  0;

        if (!is_null($mention->retries())) {
            $retries = is_string($mention->retries()) ? (int)$mention->retries() : $mention->retries()->toInt();
        }

        if ($retries >= $this->retries) {
            $this->indieDb->update(
                'queue',
                ['queueStatus', 'processLog'],
                ['failed', 'max retries reached'],
                'WHERE id = "' . $mentionId . '"'
            );

            return ['id' => $mentionId, 'queueStatus' => 'failed', 'processLog' => 'max retries reached', 'retries' => $retries];
        }

        $result = $this->receiver->processWebmention($sourceUrl, $targetUrl);

        switch ($result['status']) {
            case 'success':
                $this->indieDb->delete('queue', 'WHERE id = "' . $mentionId . '"');
                return ['id' => $mentionId, 'queueStatus' => 'success', 'processLog' => 'done', 'retries' => $retries];

            case 'error':
                $this->indieDb->update(
                    'queue',
                    ['queueStatus', 'processLog', 'retries'],
                    ['error', $result['message'], $retries + 1],
                    'WHERE id = "' . $mentionId . '"'
                );

                return ['id' => $mentionId, 'queueStatus' => 'error', 'processLog' => $result['message'], 'retries' => $retries + 1];
        }

        return ['id' => $mentionId, 'queueStatus' => $result['status'], 'processLog' => $result['message'], 'retries' => $retries + 1];
    }

    public function deleteQueueItem(string $id)
    {
        return $this->indieDb->delete('queue', 'WHERE id = "' . $id . '"');
    }

    public function cleanQueue(string $status)
    {
        $acceptableStatus = ['queued', 'error', 'failed'];
        if (!in_array($status, $acceptableStatus) || empty($status)) {
            return [];
        }

        return $this->indieDb->delete('queue', 'WHERE queueStatus = "' . $status . '"');
    }
}
