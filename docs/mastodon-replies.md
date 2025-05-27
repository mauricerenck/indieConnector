# Replies via Mastodon

If you want to enable people to reply to your posts via Mastodon, ~~you currently have to use brid.gy to do so. I am currently working on a solution that will allow you to do this without brid.gy.~~ [there is now a native solution for that](docs/collecting-responses.md).

You can sill use brid.gy.
Sign up for [brid.gy](https://brid.gy/) and sign in with with Mastodon. Choose cross posting. Follow all the steps. Brid.gy will then send you Webmentions whenever someone replies, likes or boosts your Mastodon post. This webmentions will then be collected by IndieConnector.

### Set the URL of your Mastodon post

To collect replies etc. you have to create a post on Mastodon, then link it to your page. There are two ways of doing so:

1. Switch to Mastodon, create a post and copy the URL of the post. IndieConnector comes with the field blueprint `indieconnector/fields/webmentions` which you can use to store the URL of your Mastodon post. [More details here](/docs/mastodon.md).
2. Use enable IndieConnector to post automatically. When enabling this feature, IndieConnector will create a post on Mastodon and then store the URL in its json file. [More details here](/docs/mastodon.md).

### Get the URL of the post

If you want to display te URL of your Mastodon post on your page, so that people can use it to interact with your post, you can use the following page method:

```php
<?= $page->icGetMastodonUrl(); ?>
```

This method will look for the URL in the json file and the field `mastodonStatusUrl` and return one of it. The field value will always be prioritized over the json file. If no url is found, the method will return `null`.
