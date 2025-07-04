# Posting to Mastodon / Bluesky

You can enable automatically posting to Mastodon and/or Bluesky when a new page is published. IndieConnector will then create a post with a short description and a link to the page.

## General setup

In order to post to Mastodon or Bluesky, you have to setup some fields in your `config.php`. The **textfields** is a list of fields that will be used as the text for the post, the first one found and not empty will be used. And if you want to also post an image, you have to define an **imagefield**. Use the same name as in your blueprint:

```php
'mauricerenck.indieConnector.post.textfields' => ['description'],
'mauricerenck.indieConnector.post.imagefield' => 'postImage',
```

The image field must be of type `files` and can only include one image. The image will be uploaded to Mastodon or Bluesky:

Example for the image field:

```yaml
postImage:
    label: Post Image
    type: files
    multiple: false
```

## Mastodon

If you want to create a Mastdon post when a new page is published, you can do so by setting the following options in your `config.php`:

```php
'mauricerenck.indieConnector.mastodon.enabled' => true,
'mauricerenck.indieConnector.mastodon.bearer' => 'YOUR-ACCESS-TOKEN',
'mauricerenck.indieConnector.mastodon.instance-url' => 'https://mastodon.tld',
```

Create your own access token by going to your Mastodon account settings and creating a new app. Set the app to write access and copy the token. The instance url is the url of your Mastodon instance.

### Text length

Depending on your Mastodon instance the text length may vary. The default is 500. You can set it via the following option:

```php
'mauricerenck.indieConnector.mastodon.text-length' => 500,
```

### Setting a language

If you want to use another language than your default language to use the text from, you can set the following option:

```php
'mauricerenck.indieConnector.post.prefereLanguage' => 'en',
```

This might be handy if your default language is for example German but you only want to post in English on Mastodon or Bluesky. This way the english translation is used as a source for the text or title.

### Use the permalink URL

If you want to use the permalink URL instead of the page URL, you can set the following option:

```php
'mauricerenck.indieConnector.post.usePermalinkUrl' => true,
```

This url will never change, even if you change the slug of the page. This way you can ensure that the link in your Mastodon post will always work.

### Get the URL of the post

If you want to display te URL of your Mastodon post on your page, so that people can use it to interact with your post, you can use the following page method:

```php
<?= $page->icGetMastodonUrl(); ?>
```

## Bluesky

If you want to create a Bluesky post when a new page is published, you can do so by setting the following options in your `config.php`:

```php
'mauricerenck.indieConnector.bluesky.enabled' => true,
'mauricerenck.indieConnector.bluesky.handle' => 'USERNAME.bsky.social',
'mauricerenck.indieConnector.bluesky.password' => 'YOUR-APP-PASSWORD',
```

To get your app password, go to your Bluesky account settings and go to "App Passwords". Create a new password and copy it.

## Prevent posting in certain cases

You may not always want to post to Mastodon or Bluesky, for example when you create pages like your privacy policy. You can prevent posting by setting the following option in your `config.php`:

```php
'mauricerenck.indieConnector.post.blockedTemplates' => ['privacy-policy'],
```

This will prevent posting when the template of the page is `privacy-policy`. You can add as many templates as you want.

You could also turn things around and only allow posting for certain templates:

```php
'mauricerenck.indieConnector.post.allowedTemplates' => ['note', 'article'],
```

This way only pages with the templates `note` or `article` will be posted to Mastodon or Bluesky.

### Per page

In addition you can also disable posting on a per page basis by setting the following field in the page blueprint:

```yaml
fields:
    indieConnector:
        extends: indieconnector/fields/webmentions
```

This will show a toggle in the panel that allows you to disable posting for this page.

### Posting manually

If you want to post manually, you can add the IndieConnector share button to your page blueprint:

```yaml
buttons:
    icShare: true
    preview: true
    status: true
```

You have to add the regular buttons there too, if you want to keep them, for example:

```yaml
buttons:
    icShare: true
    preview: true
    open: true
    status: true
```

See https://getkirby.com/docs/reference/panel/blueprints/page#view-buttons for more information.
