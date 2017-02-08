Pusher Bundle
=========

This bundle wraps the [bentools/pusher](https://github.com/bpolaszek/bentools-pusher) library to send Push Notifications through your Symfony application.

Installation
-----

Install the library:

```
composer require bentools/pusher-bundle
```

Add the Bundle into your `AppKernel`:
```php
# app/AppKernel.php
$bundles = [
    // ...
    new BenTools\PusherBundle\BenToolsPusherBundle(),
    // ...
];
```

Define your handlers in your `services.yml`:
```yml
services:
    bentools.pusher.handler.mozilla:
        class: BenTools\Pusher\Model\Handler\MozillaHandler
        arguments: ['@guzzle.client.default']

    bentools.pusher.handler.gcm:
        class: BenTools\Pusher\Model\Handler\GoogleCloudMessagingHandler
        arguments: ['@guzzle.client.default', '%gcm.api_key%']

```

Update your database to store push subscriptions:
```
php bin/console doctrine:schema:update --dump-sql --force
# or use Doctrine Migrations
```

Mount the `/webpush/registration` route:
```yml
# app/config/routing.yml
webpush_bundle:
    resource: "@BenToolsPusherBundle/Resources/config/routing.yml"
    prefix:   /webpush
```

Install the JS assets:

```
php bin/console assets:install --symlink
```

Embed in twig (HTTPS or localhost, and authenticated user required)

```twig
<script src="{{ asset('bundles/bentoolspusher/js/pushClient.js') }}"></script>
<script>

    var registrationUrl = '{{ path('webpushbundle_register') }}';

    new subscriber({

        'onServerMessage': function(event, serverMessage) {
            console.log(event);
        },

        'onExistingSubscription' : function(subscription) {
            console.log('Existing subscription', subscription);
        },

        'onNewSubscription' : function(subscription) {
            console.log('New subscription', subscription);
            return fetch(registrationUrl, {
                method     : 'POST',
                mode       : 'cors',
                credentials: 'include',
                cache      : 'default',
                headers    : new Headers({
                    'Accept'      : 'application/json',
                    'Content-Type': 'application/json'
                }),
                body       : JSON.stringify({subscription: subscription})
            });
        }
    });

</script>
```

Usage
------

Your `BenTools\PusherBundle\Entity\Recipient` entities implement `BenTools\Pusher\Model\Recipient\RecipientInterface` and can be used in Pusher.

PHP snippet to help:
```php
use AppBundle\Entity\User;
use BenTools\Pusher\Model\Message\Notification;
use BenTools\Pusher\Model\Push\Push;
use BenTools\PusherBundle\Entity\Recipient;

$pusher     = $this->getContainer()->get('bentools.pusher');
$user       = $this->getRepositoryOf(User::class)->findOneBy([
    'username' => 'johndoe',
]);
$recipients = $this->getContainer()->get('doctrine')->getManager()->getRepository(Recipient::class)->findRecipientsForUser($user);
$message    = new Notification('Ho hi');
$push       = new Push();

foreach ($recipients AS $recipient) {
    switch ($recipient->getClient()) {
        case Recipient::CHROME:
        case Recipient::CHROME_MOBILE:
            $push->addRecipient($recipient, $this->getContainer()->get('bentools.pusher.handler.gcm'));
            break;
        case Recipient::FIREFOX:
            $push->addRecipient($recipient, $this->getContainer()->get('bentools.pusher.handler.mozilla'));
            break;
    }

    $push->setMessage($message);

}

$pusher->push($push);
```

License
-------
MIT