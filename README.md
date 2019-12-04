# wp-mandrill-mailer

Send transactional email using Mandrill easily and without hassle

## Requirements

 - WordPress 5.x
 - PHP 5.3+
 - [Mandrill PHP SDK](https://bitbucket.org/mailchimp/mandrill-api-php/src/master/)

## Installation

Download and unzip into a subfolder of your `wp-content/plugins` folder.

Make sure the folder name is `wp-mandrill-mailer`.

Inside that folder you'll find the `lib` folder. Place the contents of the `src` folder from the Mandrill PHP SDK inside it. You should have both `Mandrill.php` and a `Mandrill` folder directly inside `lib`.

Then get the `cacert.pem` file from [this page](https://curl.haxx.se/docs/caextract.html) and upload to the root of the `wp-mandrill-mailer` folder on your WordPress instance.

Now, in your WordPress go to the **Dashboard** and then to **Plugins**, find the **Placeholders** plugin and activate it.

Then again in the Dashboard, go to **Mandrill Mailer** and enter your API key and Pool name (you may enter `Main Pool` to use the default pool).

## Usage

First create a message using the `MandrillMessage` class

```php
	# Create a MandrillMessage object and set the subject, from, to, contents, attachments, etc.
	$message = MandrillMessage::newInstance()
		->setSubject('Test')
		->setFrom( array('test@mailinator.com' => 'Me') )
		->setTo( array('test@mailinator.com' => 'Me') )
		->setTemplate(get_template_directory() . '/templates/mailing/default.mailing.html')
		->setImages(array('email_header' => get_template_directory() . '/images/mailing/header.jpg'))
		->setAttachments(array('Test.pdf' => get_template_directory() . '/files/test.pdf'))
		->setReplacements(array('%email-footer%' => 'Test'))
		->setContents('<h2>Hey you</h2>');
```

Then just send your message:

```php
	MandrillMailer::send($message);
```

The function will either return `TRUE` if the mail was sent successfully and `FALSE` otherwise.

## Licensing

MIT licensed

## Author

Author: biohzrdmx [<github.com/biohzrdmx>](https://github.com/biohzrdmx)