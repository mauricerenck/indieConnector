<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Http\Response;
use Kirby\Http\Url;


use Exception;


class PostHandler
{
    protected OptionHandler $options;
    private PageHandler $pageHandler;
    private Outbox $outbox;
    protected UrlHandler $urlHandler;
    private string $correlationId;

    public function __construct(
        ?PageHandler $pageHandler = null,
        ?OptionHandler $options = null,
        ?Outbox $outbox = null,
        ?UrlHandler $urlHandler = null,
    ) {
        $this->options = $options ?? new OptionHandler();
        $this->pageHandler = $pageHandler ?? new PageHandler();
        $this->outbox = $outbox ?? new Outbox();
        $this->urlHandler = $urlHandler ?? new UrlHandler();
        $this->correlationId = '';
    }


    /**
     * Creates and returns a Kirby Request
     *
     * This is used for easier unit testing, as we can mock this
     * @param $url string
     * @return Remote
     */
    protected function request(string $url, array $params): Remote
    {
        return Remote::request($url, $params);
    }
}
