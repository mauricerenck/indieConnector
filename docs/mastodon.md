# Mastodon

If you want to post a toot when a new page is published, you can do so:

```
'mauricerenck.indieConnector.sendMastodon' => true,
'mauricerenck.indieConnector.mastodon-bearer' => 'my-secret-token',
'mauricerenck.indieConnector.mastodon-instance-url' => 'https://mastodon.online',
'mauricerenck.indieConnector.mastodon-text-field' => 'description',
```

Insert the url of the instance your account runs on. You have to get an Bearer Token, you should be getting this from your instance, too. Go to your account settings, there should be an entry like `development` or `api` in the menu. Create a new app and set write access. Copy the token you get and use it here. You can also set a source field for the text which will be used in your toot. This text will be shortened if it's too long and the url of your page will be appended. 