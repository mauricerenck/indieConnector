<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

class MastodonSender extends ExternalPostSender
{
    public function __construct(
        private ?string $instanceUrl = null,
        private ?string $token = null,
        private ?bool $enabled = null,
        private ?int $resizeImages = null,
    ) {
        parent::__construct();

        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.mastodon.enabled', false);
        $this->instanceUrl = $instanceUrl ?? option('mauricerenck.indieConnector.mastodon.instance-url', false);
        $this->token = $token ?? option('mauricerenck.indieConnector.mastodon.bearer', false);
        $this->resizeImages = $resizeImages ?? option('mauricerenck.indieConnector.mastodon.resizeImages', 0);

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

        if (!$this->pageChecks->pageFullfillsCriteria($page, 'post')) {
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
            $message = is_null($manualTextMessage) ? $this->getTextFieldContent($page) : $manualTextMessage;
            $tags = $this->getPostTags($page);

            $fullMessage = $this->getTrimmedFullMessage(message: $message, url: $pageUrl, tags: $tags, service: 'mastodon');

            $headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'User-Agent: IndieConnector/1.0 (+' . site()->url() . ')'
            ];

            $requestBody = [
                'status' => $fullMessage,
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

                    if ($this->resizeImages !== 0) {
                        $resizedImage = $image->resize($this->resizeImages);
                        $resizedImage->base64(); // this forces kirby to generate the image
                        $image = $resizedImage;
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

            // Verwende das ursprünglich übergebene Bild direkt
            // (Die Größenoptimierung können wir später hinzufügen wenn nötig)
            $optimizedImagePath = $imagePath;

            // 2) CURLFile erzeugen
            $cfile = curl_file_create(
                $optimizedImagePath,
                mime_content_type($optimizedImagePath),
                basename($optimizedImagePath)
            );

            // 3) cURL-Request mit verbesserter Kompatibilität
            $url = rtrim($this->instanceUrl, '/') . '/api/v1/media';
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => [
                    'file' => $cfile,
                    'description' => $imageAlt
                ],
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->token,
                    'Accept: application/json',
                    'User-Agent: IndieConnector/1.0 (+' . site()->url() . ')',
                    'Expect:',  // verhindert "100-continue"-Handshake
                ],
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 4) Erfolg prüfen und ID zurückgeben
            if ($httpCode !== 200 || empty($responseBody)) {
                return false;
            }

            $responseData = json_decode($responseBody, true);
            return $responseData['id'] ?? false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }
}
