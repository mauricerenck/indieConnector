# Microformats

You can enrich your webmentions by using microformats. Those formats are defined by [microformats](https://microformats.org/wiki/microformats2) and are used to describe different types of content.

## Tell who you are

You should have a h-card on your page to tell who you are. This is a simple example of a h-card:

```html
<p class="h-card">
  <img class="u-photo" src="https://example.org/photo.png" alt="" />
  <a class="p-name u-url" href="https://example.org">Joe Bloggs</a>
  <a class="u-email" href="mailto:jbloggs@example.com">jbloggs@example.com</a>,
  <span class="p-street-address">My Street 42</span>
  <span class="p-locality">Galaxy</span>
  <span class="p-country-name">Universe</span>
</p>
```

You can leave out any information you don't want to share, but you should include at least your name and a link to your homepage.

This h-card can be placed in your footer, header or any other place on your page.

## Format your content

To inform the receiver about your content, the content should be within an h-entry. This is a simple example of an h-entry:

```html
<article class="h-entry">
  <h1 class="p-name">Hello World</h1>
  <p class="p-summary">This is my first post.</p>
  <p class="e-content">This is the <strong>full</strong> text of my blog post.</p>
  <a class="u-url" href="https://example.org/hello-world">Permalink</a>
</article>
```

## Define what type of webmention you want to send

To enable receivers to understand what type of webmention you are sending, you can use the `u-in-reply-to`, `u-like-of`, `u-repost-of` and `u-bookmark-of` classes.

```html
<a class="u-like-of" href="https://liked-page.tld">I like this</a>
<span class="u-like-of">https://liked-page-2.tld</span>
<a class="u-bookmark-of" href="https://bookmarked-page.tld">Bookmark</a>
<a class="u-in-reply-to" href="https://reply-page.tld">URL of the post being replied to</a>
<a class="u-repost-of" href="https://repost-page.tld">URL of the original post being reposted</a>
```

### Events

You can also send webmentions for events. The h-event microformat is used to describe events. This is a simple example of a h-event:

```html
<article class="h-event">
  <h1 class="p-name">IndieWebCamp Berlin 2021</h1>
  <p class="p-summary">A two-day gathering of web creators building and sharing open web technologies.</p>
  <p class="p-description">IndieWebCamp Berlin 2021 is a gathering for independent web creators of all kinds, from graphic artists, to designers, UX engineers, coders, hackers, to share ideas, actively work on creating for their own personal websites, and build upon each others creations.</p>
  <time class="dt-start" datetime="2021-11-13T09:00:00+01:00">November 13th, 2021</time> to <time class="dt-end" datetime="2021-11-14T18:00:00+01:00">November 14th, 2021</time>
  <a class="u-url" href="https://indieweb.org/2021/Berlin">Permalink</a>

  <span class="p-location h-card">
    <a class="u-url p-name p-org" href="https://wiki.mozilla.org/MozPDX">Mozilla</a>,
    <span class="p-street-address">1120 NW Couch St #320</span>,
    <span class="p-locality">Portland</span>,
    <span class="p-region">Oregon</span>,
    <span class="p-postal-code">97209</span>,
    <abbr class="p-country-name" title="United States">US</abbr>
  </span>
</article>
```

You can even invite specific person to an event:

```html
<span class="p-invitee h-card">
  <a class="u-url p-name" href="https://example.org">Joe Blogs</a>
</span>
```

or:

```html
<a class="u-invitee h-card" href="https://example.org">Alison Blogs</a>
```

### Reply to an event

You can also reply to an event to inform the organizer if you are going to attend or not. This is a simple example of a reply to an event:

```html
<div class="h-entry">
  <p class="p-summary">
    <a href="https://example.com" class="p-author h-card">Your Name</a>
    RSVPs <span class="p-rsvp">yes</span>
    to <a href="https://events.indieweb.org/example" class="u-in-reply-to">Event Name</a>
  </p>
</div>
```

You can send the following RSVPs:

- `yes` for attending
- `no` for not attending
- `maybe` for maybe attending
- `interested` for maybe attending

You could also use:

```html
<data class="p-rsvp" value="yes" />
<data class="p-rsvp" value="no" />
<data class="p-rsvp" value="maybe" />
<data class="p-rsvp" value="interested" />
```

## Conclusion

This is just a simple example of how you can use microformats to send webmentions. You can find more information about microformats on the [microformats wiki](https://microformats.org/wiki/microformats2).

Please also keep in mind that not every website supports all types of webmentions.
