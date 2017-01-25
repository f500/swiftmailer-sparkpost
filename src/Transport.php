<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

use Exception as AnyException;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use SparkPost\SparkPost;
use SparkPost\SparkPostResponse;
use Swift_DependencyContainer;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_Message;
use Swift_Transport;
use Swift_TransportException;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class Transport implements Swift_Transport
{
    /**
     * @var Swift_Events_EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var SparkPost
     */
    private $sparkpost;

    /**
     * @var PayloadBuilder
     */
    private $payloadBuilder;

    /**
     * @param string             $apiKey
     * @param Configuration|null $config
     *
     * @return Transport
     */
    public static function newInstance($apiKey, Configuration $config = null)
    {
        if ($config === null) {
            $config = new Configuration();
        }

        $eventDispatcher       = Swift_DependencyContainer::getInstance()->lookup('transport.eventdispatcher');
        $guzzle                = new GuzzleAdapter(new GuzzleClient(['timeout' => 300]));
        $sparkpost             = new SparkPost($guzzle, ['key' => $apiKey]);
        $randomNumberGenerator = new MtRandomNumberGenerator();
        $payloadBuilder        = new StandardPayloadBuilder($config, $randomNumberGenerator);

        return new self($eventDispatcher, $sparkpost, $payloadBuilder);
    }

    /**
     * @param Swift_Events_EventDispatcher $eventDispatcher
     * @param SparkPost                    $sparkpost
     * @param PayloadBuilder               $payloadBuilder
     */
    public function __construct(
        Swift_Events_EventDispatcher $eventDispatcher,
        SparkPost $sparkpost,
        PayloadBuilder $payloadBuilder
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->sparkpost       = $sparkpost;
        $this->payloadBuilder  = $payloadBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        if ($event = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($event, 'beforeSendPerformed');

            if ($event->bubbleCancelled()) {
                return 0;
            }
        }

        $payload  = $this->buildPayload($message);
        $response = $this->sendTransmission($payload);
        $body     = $response->getBody();

        $sent = isset($body['results']['total_accepted_recipients'])
            ? (int) $body['results']['total_accepted_recipients']
            : 0;

        if ($event) {
            if ($response->getStatusCode() !== 200) {
                $event->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            } elseif ($response->getBody()['results']['total_accepted_recipients'] == 0) {
                $event->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            } elseif ($response->getBody()['results']['total_rejected_recipients'] > 0) {
                $event->setResult(Swift_Events_SendEvent::RESULT_TENTATIVE);
            } else {
                $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            }

            $event->setFailedRecipients($failedRecipients);
            $this->eventDispatcher->dispatchEvent($event, 'sendPerformed');
        }

        return (int) $sent;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     * @throws Swift_TransportException
     */
    private function buildPayload(Swift_Mime_Message $message)
    {
        try {
            return $this->payloadBuilder->buildPayload($message);
        } catch (AnyException $exception) {
            throw $this->createAndDispatchTransportException(
                'Failed to build payload for a SparkPost transmission',
                $exception
            );
        }
    }

    /**
     * @param array $payload
     *
     * @return SparkPostResponse
     * @throws Swift_TransportException
     */
    private function sendTransmission(array $payload)
    {
        try {
            /** @noinspection PhpUndefinedVariableInspection */
            $promise = $this->sparkpost->transmissions->post($payload);

            if ($promise instanceof SparkPostResponse) {
                return $promise;
            }

            return $promise->wait();
        } catch (AnyException $exception) {
            throw $this->createAndDispatchTransportException(
                'Failed to send transmission to SparkPost',
                $exception
            );
        }
    }

    /**
     * @param string       $message
     * @param AnyException $exception
     *
     * @return Swift_TransportException
     */
    private function createAndDispatchTransportException($message, AnyException $exception)
    {
        $transportException = new Swift_TransportException($message, 0, $exception);

        if ($event = $this->eventDispatcher->createTransportExceptionEvent($this, $transportException)) {
            $this->eventDispatcher->dispatchEvent($event, 'exceptionThrown');
        }

        return $transportException;
    }
}
