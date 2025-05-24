<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;

class MastodonSender extends ExternalPostSender
{
    public function __construct(
        private ?string $instanceUrl = null,
        private ?string $token = null,
        private ?bool $enabled = null,
    ) {
        parent::__construct();

        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.mastodon.enabled', false);
        $this->instanceUrl = $instanceUrl ?? option('mauricerenck.indieConnector.mastodon.instance-url', false);
        $this->token = $token ?? option('mauricerenck.indieConnector.mastodon.bearer', false);

        // backwards compatibility
        if (!$instanceUrl && option('mauricerenck.indieConnector.mastodon-instance-url', false)) {
            $this->instanceUrl = option('mauricerenck.indieConnector.mastodon-instance-url');
        }

        if (!$token && option('mauricerenck.indieConnector.mastodon-bearer', false)) {
            $this->token = option('mauricerenck.indieConnector.mastodon-bearer');
        }

        if (!$enabled && option('mauricerenck.indieConnector.sendMastodon', false)) {
            $this->enabled = option('mauricerenck.indieConnector.sendMastodon');
        }
    }

    public function sendPost($page)
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->token) {
            throw new Exception('No Mastodon token set');
            return false;
        }

        if (!$this->instanceUrl) {
            throw new Exception('No Mastodon Instance set');
            return false;
        }

        if (!$this->pageChecks->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->urlChecks->isLocalUrl($page->url())) {
            throw new Exception('Local url');
            return false;
        }

        if (!$this->pageChecks->pageHasEnabledExternalPosting($page)) {
            return false;
        }

        if ($this->alreadySentToTarget('mastodon', $page)) {
            return false;
        }

        try {
            $pageUrl = $this->getPostUrl($page);
            $trimTextPosition = $this->calculatePostTextLength($pageUrl);

            $message = $this->getTextFieldContent($page, $trimTextPosition);
            $message .= "\n" . $pageUrl;

            $headers = ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'];

            $requestBody = [
                'status' => $message,
                'visibility' => 'public',
            ];

            if ($defaultLanguage = kirby()->defaultLanguage()) {
                $requestBody['language'] = $defaultLanguage->code();
            }

            if ($this->prefereLanguage !== false) {
                $requestBody['language'] = $this->prefereLanguage;
            }

            $mediaIds = [];
            if ($images = $this->getImages($page)) {

                foreach ($images->toFiles()->limit(4) as $image) {
                    if (is_null($image)) {
                        continue;
                    }

                    $imagePath = $image->root();
                    $mediaId = $this->uploadImage($imagePath);

                    if (!$mediaId) {
                        continue;
                    }

                    $mediaIds[] = $mediaId;
                }
            }

            if (count($mediaIds) > 0) {
                $requestBody['media_ids'] = $mediaIds;
            }

            $response = Remote::request($this->instanceUrl . '/api/v1/statuses', [
                'method' => 'POST',
                'headers' => $headers,
                'data' => json_encode($requestBody),
            ]);

            $result = $response->json();

            $url = $result['url'] ?? null;
            $id = $result['id'] ?? null;

            return [
                'id' => $id,
                'uri' => $url,
                'status' => 200,
                'target' => 'mastodon'
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    public function uploadImage($imagePath)
    {
        try {
            if (!F::exists($imagePath)) {
                return false;
            }

            $boundary = uniqid();
            $delimiter = '-------------' . $boundary;

            $fileData = file_get_contents($imagePath);
            $postData =
                '--' .
                $delimiter .
                "\r\n" .
                'Content-Disposition: form-data; name="file"; filename="' .
                basename($imagePath) .
                '"' .
                "\r\n" .
                'Content-Type: ' .
                mime_content_type($imagePath) .
                "\r\n\r\n" .
                $fileData .
                "\r\n" .
                '--' .
                $delimiter .
                "--\r\n";

            $response = Remote::request($this->instanceUrl . '/api/v2/media', [
                'method' => 'POST',
                'headers' => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type' => 'multipart/form-data; boundary=' . $delimiter,
                ],
                'data' => $postData,
            ]);

            $responseData = $response->json();

            if ($response->code() !== 200) {
                return false;
            }

            return $responseData['id'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }
}
