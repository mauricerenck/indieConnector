# Sending Webmentions

IndieConnector automatically sends webmentions from your site.

A page has to fullfil some criteria before sending a webmention. It has to be

1. Unlisted or listed (no draft)
2. On the allow list or
3. Not on the block list

After saving a page fullfilling this criteria, IndieConnector looks for Urls in your content and tries to send a webmention to those urls. If it doesn't find a webmention endpoint on the target page, it tries to send a pingback.

IndieConnector will create a json file next to the markdown file of the page. It contains all urls which have been processed. This way those pages won't be bothered if you change and save content later on.

## Disable sending webmentions

If you don't want to send webmentions or pingbacks. You can disable this feature by setting:

```
'mauricerenck.indieConnector.sendWebmention' => false
```

## Enable sending webmentions to yourself

If you want to send webmentions to your own host, you can disable this option. By default skipping is enabled, but there might be some reasons you want to send webmention to yourself, for example if you want to link page references this way.

```
'mauricerenck.indieConnector.skipSameHost' => false
```

## Restricting Webmentions to templates

You can restrict sending webmention to certain templates, via allow or block list

```
'mauricerenck.indieConnector.allowedTemplates' => ['template-1', 'template-2']
'mauricerenck.indieConnector.blockedTemplates' => ['template-3', 'template-4']
```

IndieConnector first looks if the template is blocked and stops then.
If it's not blocked, it'll go on and see if it's allowed.

**Be aware!** Adding a template to the blocklist, will result in blocking only this template and sending webmention for all the other templates. Adding a template to the allowlist, will result in sending webmention *only* for the allowed templates.

You can combine both options, but I recommend using the allow list *or* the block list.

**If you want to send webmentions from everywhere, just leave both options away, you don't have to set them at all.**

## Disable sending webmention per page

You can prevent a page from sending webmention by adding the IndieConnector field to your page blueprint:

```
indieConnetor:
    extends: indieconnector/fields/webmentions
```

This will show a new toggle (enabled by default). When disabling the toggle, no webmention will be sent after updating or publishing the page.
This way you can decide for every page you publish if it should send webmentions or not.

## Setting fields to look for urls

By default IndieConnector searches for urls in three fields: `text`, `description` and `intro`. To overwrite those fields you can use the `send-mention-url-fields` option. In order to parse the field correctly, you also have to set the field type. For example:

```
'mauricerenck.indieConnector.send-mention-url-fields' => [
    'textfield:text',
    'layouteditor:layout',
    'blockeditor:block'
]
```

The syntax is `fieldname:fieldtype`.

Three field types are supported (and should work for most cases

1. text - a regular text field type, can be plain text or markdown
2. layout - the kirby layout field type (can contain blocks)
3. block - the kirby block field type

The fieldname is the name you use in your blueprint and in your templates to output the value.

So if you use the blockeditor and your blueprint field is named `pageblocks` you have to set `pageblocks:block`.

## Changing the filename of the processed url json

You can change the filename of the json file including the processed url by setting

```
'mauricerenck.indieConnector.outboxFilename' => 'my-file-name.json'
```

Please make sure to do that upfront, existing files won't be renamed and webmentions will be sent again.

## Sending different types of webmentions

You can send different types of webmentions. For some of them IndieConnector comes with some kirbytags. You can use them in your content to send a specific type of webmention:

```
(like: https://example.com)
(bookmark: https://example.com)
(reply: https://example.com)
(repost: https://example.com)
```

All those tags function as the default Kirby link Kirbytag and will simply add a specific tag to it. You can use all the other options of the link tag as well. See https://getkirby.com/docs/reference/text/kirbytags/link

You can send even more different kinds of webmentions. [Have a look at the Microformats you can use](microformats.md).
