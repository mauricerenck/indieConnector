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
'mauricerenck.indieConnector.post.mastodon.enabled' => true,
'mauricerenck.indieConnector.post.mastodon.bearer' => 'YOUR-ACCESS-TOKEN',
'mauricerenck.indieConnector.post.mastodon.instance-url' => 'https://mastodon.tld',
```

Create your own access token by going to your Mastodon account settings and creating a new app. Set the app to write access and copy the token. The instance url is the url of your Mastodon instance.

### Text length

Depending on your Mastodon instance the text length may vary. The default is 500. You can set it via the following option:

```php
'mauricerenck.indieConnector.post.mastodon.text-length' => 500,
```

### Get the URL of the post

If you want to display te URL of your Mastodon post on your page, so that people can use it to interact with your post, you can use the following page method:

```php
<?= $page->icGetMastodonUrl(); ?>
```

## Bluesky

If you want to create a Bluesky post when a new page is published, you can do so by setting the following options in your `config.php`:

```php
'mauricerenck.indieConnector.post.mastodon.enabled' => true,
'mauricerenck.indieConnector.post.mastodon.bluesky.handle' => 'USERNAME.bsky.social',
'mauricerenck.indieConnector.post.mastodon.password' => 'YOUR-APP-PASSWORD',
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
