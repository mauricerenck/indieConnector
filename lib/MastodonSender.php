<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use CURLFile;

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

    public function sendPost($page, string | null $manualTextMessage = null)
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
            $message = is_null($manualTextMessage) ? $this->getTextFieldContent($page, $trimTextPosition) : Str::short($manualTextMessage, $trimTextPosition);
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

            if (isset($requestBody['language']) && empty($requestBody['language'])) {
                unset($requestBody['language']);
            }

            $mediaIds = [];
            $altField = $this->imageAltField;

            if ($images = $this->getImages($page)) {

                foreach ($images->toFiles()->limit(4) as $image) {
                    if (is_null($image)) {
                        continue;
                    }

                    $imagePath = $image->root();
                    $imageAlt = $image->{$altField}()->isNotEmpty() ? $image->{$altField}()->value() : '';
                    $mediaId = $this->uploadImage($imagePath, $imageAlt);

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

    public function uploadImage($imagePath, $imageAlt)
    {
        try {
            if (!F::exists($imagePath)) {
                return false;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->instanceUrl . '/api/v2/media');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->token
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new CURLFile($imagePath),
                'description' => $imageAlt
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            if (!isset($responseData['id'])) {
                return false;
            }

            return $responseData['id'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }
}
