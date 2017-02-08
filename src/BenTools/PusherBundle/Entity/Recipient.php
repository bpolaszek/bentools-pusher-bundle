<?php

namespace BenTools\PusherBundle\Entity;
use BenTools\Pusher\Model\Handler\PushHandlerInterface;
use BenTools\Pusher\Model\Recipient\RecipientInterface;
use Doctrine\ORM\Mapping as ORM;
use function GuzzleHttp\json_decode;

/**
 * Recipient
 *
 * @ORM\Table(name="webpush_recipient", indexes={
 *     @ORM\Index(name="client", columns={"client"}),
 *     @ORM\Index(name="device", columns={"device"}),
 *     @ORM\Index(name="registered_at", columns={"registered_at"}),
 *     @ORM\Index(name="active", columns={"active"}),
 *     @ORM\Index(name="user_class", columns={"user_class"}),
 *     @ORM\Index(name="user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="BenTools\PusherBundle\Repository\RecipientRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Recipient implements RecipientInterface {

    const FIREFOX       = 'Firefox';
    const CHROME        = 'Chrome';
    const CHROME_MOBILE = 'Chrome Mobile';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $client;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $device;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $registeredAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $userClass;

    /**
     * @var int|string
     * @ORM\Column(type="string", nullable=true)
     */
    private $userId;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    private $subscription = [];

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    private $options = [];

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this - Provides Fluent Interface
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * @param string $client
     *
     * @return $this - Provides Fluent Interface
     */
    public function setClient($client) {
        $this->client = $client;
        return $this;
    }

    /**
     * @return string
     */
    public function getDevice() {
        return $this->device;
    }

    /**
     * @param string $device
     * @return $this - Provides Fluent Interface
     */
    public function setDevice($device) {
        $this->device = $device;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRegisteredAt() {
        return $this->registeredAt;
    }

    /**
     * @param \DateTime $registeredAt
     *
     * @return $this - Provides Fluent Interface
     */
    public function setRegisteredAt($registeredAt) {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this - Provides Fluent Interface
     */
    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserClass() {
        return $this->userClass;
    }

    /**
     * @param string $userClass
     *
     * @return $this - Provides Fluent Interface
     */
    public function setUserClass($userClass) {
        $this->userClass = $userClass;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param int|string $userId
     *
     * @return $this - Provides Fluent Interface
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return array
     */
    public function getSubscription() {
        return $this->subscription;
    }

    /**
     * @param array $subscription
     *
     * @return $this - Provides Fluent Interface
     */
    public function setSubscription($subscription) {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this - Provides Fluent Interface
     */
    public function setOptions($options) {
        $this->options = $options;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOption($key) {
        return $this->options[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint(): string {
        return $this->subscription['endpoint'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setEndpoint(string $endpoint): RecipientInterface {
        $this->subscription['endpoint'] = $endpoint;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string {
        if (!$this->getEndpoint()) {
            return null;
        }
        return array_reverse(explode('/', $this->getEndpoint()))[0];
    }

    /**
     * @inheritDoc
     */
    public function getAuthKey(): ?string {
        return $this->subscription['keys'][self::AUTH_KEY];
    }

    /**
     * @inheritDoc
     */
    public function getAuthSecret(): ?string {
        return $this->subscription['keys'][self::AUTH_SECRET];
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist() {
        if (!$this->registeredAt) {
            $this->setRegisteredAt(new \DateTime());
        }
    }

    /**
     * @param $payload
     *
     * @return Recipient
     */
    public static function createFromPayload(string $payload, $client = null, $device = null) {
        return static::createFromArray(json_decode($payload, true), $client, $device);
    }

    /**
     * @param $subscriptionData
     * @return static
     */
    public static function createFromArray(array $subscriptionData, $client = null, $device = null) {
        $subscription = new static();
        $subscription->setSubscription($subscriptionData);
        $subscription->setClient($client ?? self::detectProviderFromEndpoint($subscriptionData['endpoint']));
        $subscription->setDevice($device);
        return $subscription;
    }

    /**
     * @param $endpoint
     *
     * @return int
     * @throws \RuntimeException
     */
    public static function detectProviderFromEndpoint($endpoint) {
        switch (true) {
            case strpos($endpoint, 'https://updates.push.services.mozilla.com') !== false:
                return self::FIREFOX;
            case strpos($endpoint, 'https://fcm.googleapis.com/gcm/send/') !== false:
            case strpos($endpoint, 'https://android.googleapis.com/gcm/send/') !== false:
                return self::CHROME;
            default:
                return null;
        }
    }
}

