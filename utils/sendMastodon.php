<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

class MastodonSender
{
    public function sendToot($page)
    {
        if (!option('mauricerenck.indieConnector.mastodon-bearer', false)) {
            return false;
        }

        if (!option('mauricerenck.indieConnector.mastodon-instance-url', false)) {
            return false;
        }

        if (strpos($page->url(), '//localhost') === true || strpos($page->url(), '//127.0.0') === true) {
            return false;
        }

        try {
            $tootMaxLength = 280;
            $postUrl = $page->url();
            $urlLength = Str::length($postUrl);
            $trimTextPosition = $tootMaxLength - $urlLength - 2;
            $textfield = option('mauricerenck.indieConnector.mastodon-text-field', 'description');
            $message = ($page->$textfield()->isNotEmpty()) ? $page->$textfield() : Str::short($page->title(), $trimTextPosition);
            $message .= ' ' . $postUrl;

            $headers = [
                'Authorization: Bearer ' . option('mauricerenck.indieConnector.mastodon-bearer')
            ];

            $status_data = [
                'status' => $message,
                // 'language' => 'de', // TODO
                'visibility' => 'public'
            ];

            $ch_status = curl_init();
            curl_setopt($ch_status, CURLOPT_URL, option('mauricerenck.indieConnector.mastodon-instance-url'));
            curl_setopt($ch_status, CURLOPT_POST, 1);
            curl_setopt($ch_status, CURLOPT_POSTFIELDS, $status_data);
            curl_setopt($ch_status, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_status, CURLOPT_HTTPHEADER, $headers);

            $output_status = json_decode(curl_exec($ch_status));

            curl_close($ch_status);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
