<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

class ExternalPostSender extends Sender
{
    public function __construct(
        public ?array $textfields = null,
        public ?string $imagefield = null,
        public ?string $imageAltField = null,
        public ?string $tagsField = null,
        public ?string $prefereLanguage = null,
        public ?bool $usePermalinkUrl = null,
        public ?bool $skipUrl = null,
        public ?array $skipUrlTemplates = null,
        private ?int $maxPostLength = null,
        public ?bool $neverTrimTags = null,
        public ?UrlChecks $urlChecks = null,
        public ?PageChecks $pageChecks = null
    ) {
        parent::__construct();

        $this->textfields = $textfields ?? option('mauricerenck.indieConnector.post.textfields', ['description']);
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);
        $this->imageAltField = $imageAltField ?? option('mauricerenck.indieConnector.post.imagealtfield', 'alt');
        $this->tagsField = $tagsField ?? option('mauricerenck.indieConnector.post.tagsfield', null);
        $this->prefereLanguage = $prefereLanguage ?? option('mauricerenck.indieConnector.post.prefereLanguage', null);
        $this->usePermalinkUrl = $usePermalinkUrl ?? option('mauricerenck.indieConnector.post.usePermalinkUrl', false);
        $this->skipUrl = $skipUrl ?? option('mauricerenck.indieConnector.post.skipUrl', false);
        $this->skipUrlTemplates = $skipUrlTemplates ?? option('mauricerenck.indieConnector.post.skipUrlTemplates', []);
        $this->maxPostLength = $maxPostLength ?? option('mauricerenck.indieConnector.mastodon.text-length', 300);
        $this->neverTrimTags = $neverTrimTags ?? option('mauricerenck.indieConnector.post.neverTrimTags', true);

        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();

        // backwards compatibility
        $singleTextfield = option('mauricerenck.indieConnector.post.textfield', false);
        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$maxPostLength && option('mauricerenck.indieConnector.mastodon-text-length', false)) {
            $this->maxPostLength = option('mauricerenck.indieConnector.mastodon-text-length');
        }
    }

    public function getTextFieldContent($page): string
    {
        $pageOfLanguage = !$this->prefereLanguage ? null : $page->translation($this->prefereLanguage);
        $content = !is_null($pageOfLanguage) ? $pageOfLanguage->content() : $page->content()->toArray();

        if (is_array($this->textfields)) {
            foreach ($this->textfields as $field) {
                $lowercaseField = strtolower($field);
                if (isset($content[$lowercaseField]) && !empty($content[$lowercaseField])) {
                    return $content[$lowercaseField];
                }
            }
        }

        $field = $this->textfields;
        if (!is_array($this->textfields) && isset($content[$field]) && !empty($content[$field])) {
            return $content[$field];
        }

        return isset($content['title']) ? $content['title'] : '';
    }

    public function getPostTags($page): string
    {
        if (is_null($this->tagsField)) {
            return '';
        }

        $lowercaseTagField = strtolower($this->tagsField);

        if ($page->{$lowercaseTagField}()->isEmpty()) {
            return '';
        }

        $tags = $page->{$lowercaseTagField}()->split();

        if (count($tags) > 0) {
            return '#' . implode(' #', $tags);
        }

        return '';
    }

    public function getPostUrl($page): string
    {
        if ($this->skipUrl) {
            return '';
        }

        $normalizedIntendedTemplate = strtolower($page->intendedTemplate());
        if (in_array($normalizedIntendedTemplate, $this->skipUrlTemplates)) {
            return '';
        }

        if (!is_null($page->icSkipUrl()) && $page->icSkipUrl()->isTrue()) {
            return '';
        }

        $url = $page->url($this->prefereLanguage);

        if ($this->usePermalinkUrl) {
            $url = $page->permalink();
        }

        return $url;
    }


    public function calculatePostTextLength(string $url)
    {
        $urlLength = Str::length($url);
        return $this->maxPostLength - $urlLength - 2;
    }

    public function getTrimmedFullMessage(string $message, string $url, string $tags, string $service): string
    {
        $maxLength = $service == 'mastodon' ? $this->maxPostLength : 300;
        $appendix = '...';

        $appendixLength = Str::length($appendix);
        $linebreakLength = 1;
        $metaTextLength = $appendixLength; // FIXME until kirby bug is not fixed @see https://github.com/getkirby/kirby/issues/7722

        $urlLength = Str::length($url);
        $tagsLength = Str::length($tags);

        if ($urlLength > 0) {
            $metaTextLength += $urlLength + $linebreakLength;
        }

        if ($this->neverTrimTags && $tagsLength > 0) {
            $metaTextLength += $tagsLength + $linebreakLength * 2;
        }

        $maxLength -= $metaTextLength;
        $fullMessage = Str::Short($message, $maxLength, $appendix);

        if ($urlLength > 0) {
            $fullMessage .= "\n" . $url;
        }

        if ($tagsLength > 0) {
            $fullMessage .= "\n\n" . $tags;
        }

        return $fullMessage;
    }

    public function updatePosts($id, $url, $statusCode, $page, $target)
    {
        return $this->updateExternalPosts($id, $url, $statusCode, $target, $page);
    }

    public function getImages($page)
    {
        if ($this->imagefield) {
            $imagefield = $this->imagefield;
            $images = $page->$imagefield();

            if ($images->isEmpty()) {
                return false;
            }

            return $images;
        }

        return false;
    }

    public function getActiveServices($page)
    {
        $services = [];

        if (option('mauricerenck.indieConnector.mastodon.enabled', false)) {
            $postData = $this->getPostTargetUrlAndStatus('mastodon', $page);

            $services[] = [
                'name' => 'mastodon',
                'label' => 'Mastodon',
                'icon' => 'mastodon',
                'url' => $postData['url'],
                'status' => $postData['status']
            ];
        }

        if (option('mauricerenck.indieConnector.bluesky.enabled', false)) {
            $postData = $this->getPostTargetUrlAndStatus('bluesky', $page);

            if (!is_null($postData['url'])) {
                $bluesky = new Bluesky();
                $url = $bluesky->getUrlFromDid($postData['url']);
            } else {
                $url = null;
            }

            $services[] = [
                'name' => 'bluesky',
                'label' => 'Bluesky',
                'icon' => 'bluesky',
                'url' => $url,
                'status' => $postData['status']
            ];
        }

        return $services;
    }

    public function getServicesDialogFields($page)
    {
        $textfield = (is_array($this->textfields)) ? $this->textfields[0] : $this->textfields;

        $services = $this->getActiveServices($page);
        $isDraft = $page->isDraft();

        $fields = [];
        $fields['text'] = [
            'label' => 'Text',
            'type' => 'textarea',
            'buttons' => false,
            'size' => 'small',
            'maxlength' => 500,
            'required' => true,
        ];

        $fields['skipUrl'] = [
            'label' => 'Skip posting URL',
            'type' => 'toggle',
            'width' => '1/3'
        ];

        $fields['savePostText'] = [
            'label' => 'Overwrite field "' . $textfield . '" with this text',
            'type' => 'toggle',
            'width' => '2/3',
            'disabled' => $isDraft
        ];

        $fields['overwriteField'] = [
            'type' => 'hidden',
            'value' => $textfield,
        ];

        if ($isDraft) {
            $fields['overwriteField'] = [
                'type' => 'info',
                'theme' => 'info',
                'text' => 'This will post to all configured services once the page is published!',
            ];
        }

        $sentData = [];
        foreach ($services as $service) {
            $sentDataEntry = [
                'text' => $service['label'],
                'selecting' => true,
                'selectable' => true,
                'value' => $service['name'],
                'link' => false,
                'image' => [
                    'icon' => $service['name'],
                    'ratio' => '1/1',
                    'back' => 'transparent'
                ]
            ];

            if (!is_null($service['url']) && $service['status'] === 'success') {
                $sentDataEntry['info'] = 'Published';
                $sentDataEntry['selecting'] = false;
                $sentDataEntry['selectable'] = false;
                $sentDataEntry['buttons'] = [['icon' => 'open', 'link' => $service['url'], 'target' => '_blank']];
            } else if (!is_null($service['url']) && $service['status'] === 'error') {
                $sentDataEntry['info'] = 'Error';
            }

            $sentData[] = $sentDataEntry;
        }

        if (count($sentData) > 0 && !$isDraft) {
            $fields['services'] = [
                'type' => 'icPostStatus',
                'serviceItems' => $sentData
            ];
        }

        return $fields;
    }
}
