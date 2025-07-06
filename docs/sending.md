# Sending Webmentions

IndieConnector automatically sends webmentions from your site.

A page has to fullfil some criteria before a webmention is sent. It has to be

-   No draft
-   On the allow list
-   Not on the block list

After saving a page fullfilling this criteria, IndieConnector looks for Urls in your content and tries to send a webmention to those urls. If it doesn't find a webmention endpoint on the target page, it tries to send a pingback.

IndieConnector will create a json file next to the markdown file of the page. It contains all urls which have been processed.

## Disable sending webmentions

If you don't want to send webmentions or pingbacks. You can disable this feature by setting:

```
'mauricerenck.indieConnector.send.enabled' => false
```

## Sending webmentions manually

If you want to send webmentions manually, you can disable the automatic sending:

```
'mauricerenck.indieConnector.send.automatically' => false
```

Add the panel view button to your page blueprint:

```yaml
buttons:
    icWebmentions: true
```

You can now send webmentions manually by clicking the button in the panel.

## Setting fields to look for urls

By default IndieConnector searches for urls in three fields: `text`, `description` and `intro`. To overwrite those fields you can use the `send.url-fields` option. In order to parse the field correctly, you also have to set the field type. For example:

```
'mauricerenck.indieConnector.send.url-fields' => [
    'textfield:text',
    'layoutfield:layout',
    'blockfield:block'
]
```

The syntax is `fieldname:fieldtype`.

Three field types are supported (and should work for most cases)

1. text - a regular text field type, can be plain text or markdown
2. layout - the kirby layout field type (can contain blocks)
3. block - the kirby block field type

The fieldname is the name you use in your blueprint and in your templates to output the value.

So if you use the blockeditor and your blueprint field is named `pageblocks` you have to set `pageblocks:block`.

## Restricting Webmentions to templates

You can restrict sending webmention to certain templates, via allow or block list

```
'mauricerenck.indieConnector.send.allowedTemplates' => ['template-1', 'template-2']
'mauricerenck.indieConnector.send.blockedTemplates' => ['template-3', 'template-4']
```

IndieConnector first looks if the template is blocked and stops then.
If it's not blocked, it'll go on and see if it's allowed.

**Be aware!** Adding a template to the blocklist, will result in blocking only this template and sending webmention for _all_ the other templates. Adding a template to the allowlist, will result in sending webmention _only_ for the allowed templates.

You can combine both options, but I recommend using the allow list _or_ the block list.

**If you want to send webmentions from everywhere, just leave both options away, you don't have to set them at all.**

## Disable sending webmention per page

You can prevent a page from sending webmention by adding the IndieConnector field to your page blueprint:

```yaml
indieConnetor:
    extends: indieconnector/fields/webmentions
```

This will show a toggle (enabled by default). When disabling the toggle, no webmention will be sent after updating or publishing the page.

## Mark pages as deleted

When you delete a page in Kirby, accessing the page after that will result in a `404`. If your page included a webmention you may want to inform the receiver, that the page has been deleted. In this case Kirby has to return a `410 Gone` status code. Therefore IndieConnector will store deleted pages in a sqlite database and return a `410` status code for those pages.

To enable this feature you have to set the following option:

```php
'mauricerenck.indieConnector.send.markDeleted' => true
```

You also have to configure the path to the sqlite database, as described in the [database](database.md) documentation.

\*_Be aware_ that this will result in a database lookup for _every_ page request. It would be good thing to inform receivers about deleted pages, but it may slow down your site. So use this feature with caution.

## Setting number of retries

If a webmention fails, IndieConnector will retry sending it three times. You can set the number of retries by setting:

```php
'mauricerenck.indieConnector.send.maxRetries' => 3
```

## Enable sending webmentions to yourself

If you want to send webmentions to your own host, you can disable the `skipSameHost` option. By default skipping is enabled, but there might be some reasons you want to send webmention to yourself, for example if you want to link page references this way.

```
'mauricerenck.indieConnector.send.skipSameHost' => false
```

## Changing the filename of the processed url json

You can change the filename of the json file including the processed url by setting

```
'mauricerenck.indieConnector.send.outboxFilename' => 'my-file-name.json'
```

Please make sure to do that upfront, existing files won't be renamed!

## Sending different types of webmentions

You can send different types of webmentions. For some of them IndieConnector comes with some kirbytags. You can use them in your content to send a specific type of webmention:

```
(like: https://example.com)
(bookmark: https://example.com)
(reply: https://example.com)
(repost: https://example.com)
```

All those tags function as the default Kirby link Kirbytag and will simply add a specific microformats to it. You can use all the other options of the link tag as well. See https://getkirby.com/docs/reference/text/kirbytags/link

You can send even more different kinds of webmentions. [Have a look at the Microformats you can use](microformats.md).
