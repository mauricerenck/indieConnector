<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;
use Exception;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;

class MastodonSender extends Sender
{
    public function __construct(
        private ?int $tootMaxLength = null,
        private ?string $textfield = null,
        private ?string $imagefield = null,
        private ?string $instanceUrl = null,
        private ?string $token = null,
        private ?bool $enabled = null,
        private ?UrlChecks $urlChecks = null,
        private ?PageChecks $pageChecks = null
    ) {
        $this->textfield = $textfield ?? option('mauricerenck.indieConnector.post.textfield', 'description');
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);

        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.mastodon.enabled', false);
        $this->instanceUrl = $instanceUrl ?? option('mauricerenck.indieConnector.mastodon.instance-url', false);
        $this->token = $token ?? option('mauricerenck.indieConnector.mastodon.bearer', false);
        $this->tootMaxLength = $tootMaxLength ?? option('mauricerenck.indieConnector.mastodon.text-length', 500);

        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();

        // backwards compatibility
        if (!$textfield && option('mauricerenck.indieConnector.mastodon-text-field', false)) {
            $this->textfield = option('mauricerenck.indieConnector.mastodon-text-field');
        }

        if (!$tootMaxLength && option('mauricerenck.indieConnector.mastodon-text-length', false)) {
            $this->tootMaxLength = option('mauricerenck.indieConnector.mastodon-text-length');
        }

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

        try {
            $pageUrl = $page->url();
            $trimTextPosition = $this->calculatePostTextLength($page->url());
            $textfield = $this->textfield;

            $message = $page->$textfield()->isNotEmpty()
                ? $page->$textfield()->value()
                : Str::short($page->title(), $trimTextPosition);
            $message .= "\n" . $pageUrl;

            $headers = ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'];

            $requestBody = [
                'status' => $message,
                'visibility' => 'public',
            ];

            if ($defaultLanguage = kirby()->defaultLanguage()) {
                $requestBody['language'] = $defaultLanguage->code();
            }

            if ($mediaId = $this->uploadImage($page)) {
                $requestBody['media_ids'] = [$mediaId];
            }

            Remote::request($this->instanceUrl . '/api/v1/statuses', [
                'method' => 'POST',
                'headers' => $headers,
                'data' => json_encode($requestBody),
            ]);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    public function uploadImage($page)
    {
        try {
            if ($this->imagefield) {
                $imagefield = $this->imagefield;
                $image = $page->$imagefield();

                if (!is_null($image) && $image->isNotEmpty()) {
                    $imagePath = $image->toFile()->root();

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
                }
            }

            return false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    public function calculatePostTextLength(string $url)
    {
        $urlLength = Str::length($url);
        return $this->tootMaxLength - $urlLength - 2;
    }
}
