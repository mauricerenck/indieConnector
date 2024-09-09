<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Url;

class Microformats
{
    private $urlTypes = ['like-of', 'repost-of', 'bookmark-of', 'in-reply-to'];
    public function __construct(private $pageUrl = null, private $contentHtml = null)
    {
        $this->contentHtml = $contentHtml ?? option('mauricerenck.indieConnector.receive.useHtmlContent', false);
    }

    public function returnArraySave($values)
    {
        return is_array($values) ? $values : [$values];
    }

    public function includesPageUrl(array|string $urls)
    {
        $urls = $this->returnArraySave($urls);
        $url_validation_regex = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";

        foreach ($urls as $url) {
            if(preg_match($url_validation_regex, $url) !== 1) {
                continue;
            }

            $trimmedUrl = trim(Url::stripQuery($url));
            if ($trimmedUrl === $this->pageUrl) {
                return true;
            }
        }

        return false;
    }

    public function includesBaseUrl(array|string $urls)
    {
        $urls = $this->returnArraySave($urls);
        foreach ($urls as $url) {
            $trimmedHost = trim(Url::stripPath($url));
            $pageHost = trim(Url::stripPath($this->pageUrl));
            $pageHost = rtrim($pageHost, '/');

            if ($trimmedHost === $pageHost) {
                return true;
            }
        }

        return false;
    }

    public function getTypes(array $microformats): array
    {
        if (empty($microformats['items'])) {
            return null;
        }

        $types = [];

        foreach ($microformats['items'] as $item) {
            if (!isset($item['type'])) {
                continue;
            }

            if (in_array('h-entry', $item['type'])) {
                if (!isset($item['properties'])) {
                    continue;
                }

                foreach ($item['properties'] as $property => $values) {
                    if (in_array($property, $this->urlTypes) && $this->includesPageUrl($values)) {
                        $types[] = $property;
                    }
                }

                if (!isset($item['children'])) {
                    continue;
                }

                foreach ($item['children'] as $child) {
                    if (!isset($child['type'])) {
                        continue;
                    }

                    if (in_array('h-event', $child['type'])) {
                        if ($this->isInvitedToEvent($child)) {
                            $types[] = 'invite';
                        }
                    }
                }
            }

            if (in_array('h-event', $item['type'])) {
                if ($this->isInvitedToEvent($item)) {
                    $types[] = 'invite';
                }
            }
        }

        return $types;
    }

    public function isInvitedToEvent(array $event): bool
    {
        if (!isset($event['properties'])) {
            return false;
        }

        $eventProperty = $event['properties'];

        if (!isset($eventProperty['invitee'])) {
            return false;
        }

        foreach ($eventProperty['invitee'] as $invitee) {
            $url = null;

            if (isset($invitee['value'])) {
                $url = $invitee['value'];
            } elseif (isset($invitee['properties']['url'])) {
                $url = $invitee['properties']['url'];
            }

            if (is_null($url)) {
                return false;
            }

            if ($this->includesBaseUrl($url)) {
                return true;
            }
        }

        return false;
    }

    public function getAuthor($microformats)
    {
        if (empty($microformats['items'])) {
            return null;
        }

        $authorHCard = [];
        $authorHEntry = [];
        $authorHEntryAlternative = [];
        $authorInfos = [
            'name' => null,
            'url' => null,
            'photo' => null,
        ];

        foreach ($microformats['items'] as $item) {
            if (in_array('h-card', $item['type'])) {
                $authorHCard = $this->getAuthorFromHCard($item);
            }

            if (in_array('h-entry', $item['type'])) {
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (in_array('h-card', $child['type'])) {
                            $authorHEntry = $this->getAuthorFromHCard($child);
                        }
                    }
                }

                if (isset($item['properties'])) {
                    if (isset($item['properties']['author'])) {
                        $authorHEntryAlternative = $this->getAuthorFromHCard($item['properties']['author'][0]);
                    }
                }
            }
        }

        $mergedValues = array_merge_recursive($authorHCard, $authorHEntry, $authorHEntryAlternative);

        foreach ($mergedValues as $key => $values) {
            $values = $this->returnArraySave($values);
            foreach ($values as $value) {
                if (!is_null($value)) {
                    $authorInfos[$key] = $value;
                }
            }
        }

        return $authorInfos;
    }

    public function getAuthorName(array $hCard): string|null
    {
        if (!isset($hCard['properties']['name'])) {
            return null;
        }

        $name = $this->returnArraySave($hCard['properties']['name']);

        return !isset($name[0]) ? null : $name[0];
    }

    public function getAuthorUrl(array $hCard): string|null
    {
        if (!isset($hCard['properties']['url'])) {
            return null;
        }

        $url = $this->returnArraySave($hCard['properties']['url']);

        return !isset($url[0]) ? null : $url[0];
    }

    public function getAuthorPhoto(array $hCard): string|null
    {
        if (!isset($hCard['properties']['photo'])) {
            return null;
        }

        $photo = $this->returnArraySave($hCard['properties']['photo']);

        return !isset($photo[0]['value']) ? null : $photo[0]['value'];
    }

    public function getAuthorFromHCard(array $hCard)
    {
        if (!isset($hCard['properties'])) {
            return null;
        }

        $name = $this->getAuthorName($hCard);
        $url = $this->getAuthorUrl($hCard);
        $photo = $this->getAuthorPhoto($hCard);

        return [
            'name' => $name,
            'url' => $url,
            'photo' => $photo,
        ];
    }

    public function getSummaryOrContent(array $microformats)
    {
        if (empty($microformats['items'])) {
            return null;
        }

        $summary = null;
        $content = null;

        foreach ($microformats['items'] as $item) {
            if (in_array('h-entry', $item['type'])) {
                if (isset($item['properties'])) {
                    $properties = $item['properties'];

                    if (isset($properties['summary']) && !empty($properties['summary'])) {
                        $summary = $properties['summary'][0];
                    }

                    if (isset($properties['content']) && !empty($properties['content'])) {
                        $contentArray = $properties['content'][0];
                        $content = $this->contentHtml ? $contentArray['html'] : $contentArray['value'];
                    }
                }
            }
        }

        return $summary ?? $content;
    }

    public function getTitle(array $microformats)
    {
        if (empty($microformats['items'])) {
            return null;
        }

        foreach ($microformats['items'] as $item) {
            if (in_array('h-entry', $item['type'])) {
                if (isset($item['properties'])) {
                    if (isset($item['properties']['name'])) {
                        return $item['properties']['name'][0];
                    }
                }
            }
        }

        return null;
    }

    public function getPublishDate(array $microformats)
    {
        if (empty($microformats['items'])) {
            return date('Y-m-d\TH:i:sP', time());
        }

        foreach ($microformats['items'] as $item) {
            if (in_array('h-entry', $item['type'])) {
                if (isset($item['properties'])) {
                    if (isset($item['properties']['published'])) {
                        return $item['properties']['published'][0];
                    }
                }
            }
        }

        return date('Y-m-d\TH:i:sP', time());
    }
}
