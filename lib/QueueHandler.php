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
        $limitQuery = $limit > 0 ? ' LIMIT ' . $limit : '';
        $queue = $this->indieDb->select(
            'queue',
            ['id', 'sourceUrl', 'targetUrl', 'retries'],
            'WHERE queueStatus = "queued" OR queueStatus = "error"' . $limitQuery
        );

        if (empty($queue)) {
            return;
        }

        foreach ($queue as $mention) {
            $sourceUrl = $mention->sourceUrl();
            $targetUrl = $mention->targetUrl();
            $mentionId = $mention->id();
            $retries = $mention->retries()->toInt();

            if ($retries >= $this->retries) {
                $this->indieDb->update(
                    'queue',
                    ['queueStatus', 'processLog'],
                    ['failed', 'max retries reached'],
                    'WHERE id = "' . $mentionId . '"'
                );

                continue;
            }

            $result = $this->receiver->processWebmention($sourceUrl, $targetUrl);

            switch ($result['status']) {
                case 'success':
                    $this->indieDb->delete('queue', 'WHERE id = "' . $mentionId . '"');
                    break;

                case 'error':
                    $this->indieDb->update(
                        'queue',
                        ['queueStatus', 'processLog', 'retries'],
                        ['error', $result['message'], $retries + 1],
                        'WHERE id = "' . $mentionId . '"'
                    );

                    break;
            }
            return $result;
        }
    }
}
