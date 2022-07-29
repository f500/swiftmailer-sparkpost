<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument as Arg;
use Prophecy\Prophecy\ObjectProphecy;
use SparkPost\SparkPost;
use SparkPost\SparkPostPromise;
use SparkPost\SparkPostResponse;
use SparkPost\Transmission;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Events_TransportExceptionEvent;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use SwiftSparkPost\Exception;
use SwiftSparkPost\Message;
use SwiftSparkPost\PayloadBuilder;
use SwiftSparkPost\Transport;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class TransportTest extends TestCase
{
    /**
     * @var Swift_Events_EventDispatcher|ObjectProphecy
     */
    private $eventDispatcher;

    /**
     * @var SparkPost|ObjectProphecy
     */
    private $sparkpost;

    /**
     * @var Transmission|ObjectProphecy
     */
    private $sparkpostTransmission;

    /**
     * @var PayloadBuilder|ObjectProphecy
     */
    private $payloadBuilder;

    /**
     * @var Transport
     */
    private $transport;

    protected function setUp(): void
    {
        $this->eventDispatcher       = $this->prophesize(Swift_Events_EventDispatcher::class);
        $this->sparkpost             = $this->prophesize(SparkPost::class);
        $this->sparkpostTransmission = $this->prophesize(Transmission::class);
        $this->payloadBuilder        = $this->prophesize(PayloadBuilder::class);

        $this->sparkpost->transmissions = $this->sparkpostTransmission->reveal();

        $this->transport = new Transport(
            $this->eventDispatcher->reveal(),
            $this->sparkpost->reveal(),
            $this->payloadBuilder->reveal()
        );
    }

    protected function tearDown(): void
    {
        $this->eventDispatcher       = null;
        $this->sparkpost             = null;
        $this->sparkpostTransmission = null;
        $this->payloadBuilder        = null;
        $this->transport             = null;
    }

    /**
     * @test
     */
    public function it_can_instantiate_itself()
    {
        $transport = Transport::newInstance('some-api-key');

        $this->assertInstanceOf(Transport::class, $transport);
    }

    /**
     * @test
     */
    public function it_is_alway_considered_started()
    {
        $this->assertTrue($this->transport->isStarted());

        $this->transport->start();
        $this->assertTrue($this->transport->isStarted());

        $this->transport->stop();
        $this->assertTrue($this->transport->isStarted());
    }

    /**
     * @test
     */
    public function it_registers_a_plugin_at_the_event_dispatcher()
    {
        /** @var Swift_Events_EventListener|ObjectProphecy $eventListener */
        $eventListener = $this->prophesize(Swift_Events_EventListener::class);

        $this->transport->registerPlugin($eventListener->reveal());

        $this->eventDispatcher->bindEventListener($eventListener->reveal())
            ->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_sends_a_plain_swift_message()
    {
        $message = new Swift_Message();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenTransmissionIsSentSuccessfully($payload);

        $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(2, $sent);
    }

    /**
     * @test
     */
    public function it_also_sends_an_extended_message()
    {
        $message = Message::newInstance();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenTransmissionIsSentSuccessfully($payload);

        $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(2, $sent);
        $this->assertEquals(11668787484950529, $message->getTransmissionId());
    }

    /**
     * @test
     */
    public function it_sends_a_message_asynchronously()
    {
        $message = new Swift_Message();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenAsynchronousTransmissionIsSentSuccessfully($payload);

        $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(2, $sent);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_payload_fails_to_build()
    {
        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Failed to build payload for a SparkPost transmission');

        $message = new Swift_Message();

        $this->whenBeforeSendPerformedEventIsDispatched($message);
        $this->whenBuildingPayloadFails($message);

        $this->thenExceptionThrownIsDispatched();

        $this->transport->send($message);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_transmission_fails_to_send()
    {
        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Failed to send transmission to SparkPost');

        $message = new Swift_Message();

        $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenSendingTransmissionFails($payload);

        $this->thenExceptionThrownIsDispatched();

        $this->transport->send($message);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_response_has_no_results()
    {
        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('Failed to send transmission to SparkPost');

        $message = new Swift_Message();

        $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenSendingTransmissionHasNoResults($payload);

        $this->thenExceptionThrownIsDispatched();

        $this->transport->send($message);
    }

    /**
     * @test
     */
    public function it_behaves_normally_when_the_response_has_a_non_200_status_code()
    {
        $message = new Swift_Message();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenSendingTransmissionHasNon200StatusCode($payload);

        $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(2, $sent);
    }

    /**
     * @test
     */
    public function it_dispatches_a_failed_result_when_no_recipients_were_accepted()
    {
        $message = new Swift_Message();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenTransmissionIsSentSuccessfully($payload, 0, 2);

        $event->setResult(Swift_Events_SendEvent::RESULT_FAILED)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(0, $sent);
    }

    /**
     * @test
     */
    public function it_dispatches_a_tentative_result_when_some_recipients_were_rejected()
    {
        $message = new Swift_Message();

        $event   = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $payload = $this->whenPayloadIsBuilt($message);
        $this->whenTransmissionIsSentSuccessfully($payload, 1, 1);

        $event->setResult(Swift_Events_SendEvent::RESULT_TENTATIVE)
            ->shouldBeCalled();

        $this->thenSendPerformedEventIsDispatched($event);

        $sent = $this->transport->send($message);

        $this->assertSame(1, $sent);
    }

    /**
     * @test
     */
    public function it_sends_nothing_when_BeforeSendPerformedEvent_cancels_bubbling()
    {
        $message = new Swift_Message();

        $event = $this->whenBeforeSendPerformedEventIsDispatched($message);
        $event->bubbleCancelled()
            ->willReturn(true);

        $sent = $this->transport->send($message);

        $this->assertSame(0, $sent);
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     *
     * @return Swift_Events_SendEvent|ObjectProphecy
     */
    private function whenBeforeSendPerformedEventIsDispatched(Swift_Mime_SimpleMessage $message)
    {
        /** @var Swift_Events_SendEvent|ObjectProphecy $event */
        $event = $this->prophesize(Swift_Events_SendEvent::class);

        $event->bubbleCancelled()
            ->willReturn(false);

        $event->setFailedRecipients([])
            ->willReturn();

        $this->eventDispatcher->createSendEvent($this->transport, $message)
            ->willReturn($event->reveal());

        $this->eventDispatcher->dispatchEvent($event->reveal(), 'beforeSendPerformed')
            ->shouldBeCalled();

        return $event;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     *
     * @return array
     */
    private function whenPayloadIsBuilt(Swift_Mime_SimpleMessage $message)
    {
        $payload = [];

        $this->payloadBuilder->buildPayload($message)
            ->willReturn($payload);

        return $payload;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     */
    private function whenBuildingPayloadFails(Swift_Mime_SimpleMessage $message)
    {
        $this->payloadBuilder->buildPayload($message)
            ->willThrow(Exception::class);
    }

    /**
     * @param array $payload
     * @param int   $accepted
     * @param int   $rejected
     *
     * @return ObjectProphecy|SparkPostResponse
     */
    private function whenTransmissionIsSentSuccessfully(array $payload, $accepted = 2, $rejected = 0)
    {
        /** @var SparkPostResponse|ObjectProphecy $response */
        $response = $this->prophesize(SparkPostResponse::class);

        $response->getStatusCode()
            ->willReturn(200);

        $response->getBody()
            ->willReturn(
                [
                    'results' => [
                        'total_rejected_recipients' => $rejected,
                        'total_accepted_recipients' => $accepted,
                        'id'                        => '11668787484950529',
                    ],
                ]
            );

        $this->sparkpostTransmission->post($payload)
            ->willReturn($response->reveal());

        return $response;
    }

    /**
     * @param array $payload
     *
     * @return SparkPostResponse|ObjectProphecy
     */
    private function whenAsynchronousTransmissionIsSentSuccessfully(array $payload)
    {
        /** @var SparkPostResponse|ObjectProphecy $response */
        $response = $this->prophesize(SparkPostResponse::class);

        $response->getStatusCode()
            ->willReturn(200);

        $response->getBody()
            ->willReturn(
                [
                    'results' => [
                        'total_rejected_recipients' => 0,
                        'total_accepted_recipients' => 2,
                        'id'                        => '11668787484950529',
                    ],
                ]
            );

        /** @var SparkPostPromise|ObjectProphecy $response */
        $promise = $this->prophesize(SparkPostPromise::class);

        $promise->wait()
            ->willReturn($response->reveal());

        $this->sparkpostTransmission->post($payload)
            ->willReturn($promise->reveal());

        return $response;
    }

    /**
     * @param array $payload
     *
     * @return SparkPostResponse|ObjectProphecy
     */
    private function whenSendingTransmissionHasNoResults(array $payload)
    {
        /** @var SparkPostResponse|ObjectProphecy $response */
        $response = $this->prophesize(SparkPostResponse::class);

        $response->getStatusCode()
            ->willReturn(400);

        $response->getBody()
            ->willReturn(
                [
                    'errors' => [
                        [
                            'description' => 'Unconfigured or unverified sending domain.',
                            'code'        => '7001',
                            'message'     => 'Invalid domain',
                        ],
                    ],
                ]
            );

        $this->sparkpostTransmission->post($payload)
            ->willReturn($response->reveal());

        return $response;
    }

    /**
     * @param array $payload
     *
     * @return ObjectProphecy|SparkPostResponse
     */
    private function whenSendingTransmissionHasNon200StatusCode(array $payload)
    {
        /** @var SparkPostResponse|ObjectProphecy $response */
        $response = $this->prophesize(SparkPostResponse::class);

        $response->getStatusCode()
            ->willReturn(200);

        $response->getBody()
            ->willReturn(
                [
                    'errors'  => [
                        [
                            'message'     => 'Message generation rejected',
                            'description' => 'recipient address suppressed due to customer policy',
                            'code'        => '1902',
                        ],
                    ],
                    'results' => [
                        'total_rejected_recipients' => 0,
                        'total_accepted_recipients' => 2,
                        'id'                        => '11668787484950529',
                    ],
                ]
            );

        $this->sparkpostTransmission->post($payload)
            ->willReturn($response->reveal());

        return $response;
    }

    /**
     * @param array $payload
     */
    private function whenSendingTransmissionFails(array $payload)
    {
        $this->sparkpostTransmission->post($payload)
            ->willThrow(Exception::class);
    }

    /**
     * @param ObjectProphecy $event
     */
    private function thenSendPerformedEventIsDispatched(ObjectProphecy $event)
    {
        $this->eventDispatcher->dispatchEvent($event->reveal(), 'sendPerformed')
            ->shouldBeCalled();
    }

    private function thenExceptionThrownIsDispatched()
    {
        $this->eventDispatcher->createTransportExceptionEvent($this->transport, Arg::type('Swift_TransportException'))
            ->will(
                function (array $args) {
                    return new Swift_Events_TransportExceptionEvent($args[0], $args[1]);
                }
            );

        $this->eventDispatcher->dispatchEvent(Arg::type('Swift_Events_TransportExceptionEvent'), 'exceptionThrown')
            ->shouldBeCalled();
    }
}
