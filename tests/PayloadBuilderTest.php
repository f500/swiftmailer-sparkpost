<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit_Framework_TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swift_Attachment;
use Swift_Message;
use SwiftSparkPost\Configuration;
use SwiftSparkPost\Message;
use SwiftSparkPost\RandomNumberGenerator;
use SwiftSparkPost\StandardPayloadBuilder;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class PayloadBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var StandardPayloadBuilder
     */
    private $payloadBuilder;

    /**
     * @var RandomNumberGenerator|ObjectProphecy
     */
    private $randomNumberGenerator;

    protected function setUp()
    {
        $this->randomNumberGenerator = $this->prophesize(RandomNumberGenerator::class);

        $this->payloadBuilder = new StandardPayloadBuilder(
            new Configuration(),
            $this->randomNumberGenerator->reveal()
        );
    }

    protected function tearDown()
    {
        $this->payloadBuilder = null;
    }

    /**
     * @test
     */
    public function it_builds_the_payload_for_a_plain_swift_message()
    {
        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com'])
            ->setSubject('Hello there!')
            ->setBody('This is a special message just for you.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there!',
                'from'    => 'me@domain.com',
                'text'    => 'This is a special message just for you.',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_same_payload_for_an_extended_message()
    {
        $message = Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com'])
            ->setSubject('Hello there!')
            ->setBody('This is a special message just for you.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there!',
                'from'    => 'me@domain.com',
                'text'    => 'This is a special message just for you.',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_for_a_full_message()
    {
        $message = Message::newInstance();
        $message->setFrom('me@domain.com', 'Me');
        $message->setReplyTo('noreply@domain.com', 'No Reply');
        $message->setTo(['john@doe.com' => 'John']);
        $message->setCc(['jane@doe.com' => 'Jane']);
        $message->setBcc(['jake@doe.com' => 'Jake']);
        $message->setSubject('Hello there!');
        $message->setBody('<html><body><p>This is a special message just for you.</p></body></html>', 'text/html');
        $message->addPart('This is a special message just for you.', 'text/plain');
        $message->getHeaders()->addTextHeader('X-Custom', 'some-custom-header');

        $attachment = Swift_Attachment::newInstance('Some text in a file.', 'textfile.txt', 'text/plain');
        $message->attach($attachment);

        $message->setCampaignId('some-campaign');
        $message->setPerRecipientTags('john@doe.com', ['eget', 'bibendum']);
        $message->setPerRecipientTags('jane@doe.com', ['nunc']);
        $message->setMetadata(['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur']);
        $message->setPerRecipientMetadata('john@doe.com', ['adipiscing' => 'elit', 'donec' => 'vitae']);
        $message->setPerRecipientMetadata('jane@doe.com', ['arcu' => 'non']);
        $message->setSubstitutionData(['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper']);
        $message->setPerRecipientSubstitutionData('john@doe.com', ['rutrum' => 'sed', 'vel' => 'nunc']);
        $message->setPerRecipientSubstitutionData('jane@doe.com', ['mollis' => 'luctus']);
        $message->setOptions(
            [
                Configuration::OPT_TRANSACTIONAL    => false,
                Configuration::OPT_OPEN_TRACKING    => false,
                Configuration::OPT_CLICK_TRACKING   => false,
                Configuration::OPT_SANDBOX          => true,
                Configuration::OPT_SKIP_SUPPRESSION => true,
                Configuration::OPT_INLINE_CSS       => true,
                Configuration::OPT_IP_POOL          => 'some-ip-pool',
            ]
        );

        $expectedPayload = [
            'recipients'        => [
                [
                    'address'           => ['email' => 'john@doe.com', 'name' => 'John'],
                    'tags'              => ['eget', 'bibendum'],
                    'metadata'          => ['adipiscing' => 'elit', 'donec' => 'vitae'],
                    'substitution_data' => ['rutrum' => 'sed', 'vel' => 'nunc'],
                ],
                [
                    'address'           => ['email' => 'jane@doe.com', 'name' => 'Jane'],
                    'tags'              => ['nunc'],
                    'metadata'          => ['arcu' => 'non'],
                    'substitution_data' => ['mollis' => 'luctus'],
                ],
                [
                    'address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com'],
                ],
            ],
            'content'           => [
                'subject'     => 'Hello there!',
                'from'        => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to'    => 'noreply@domain.com',
                'html'        => '<html><body><p>This is a special message just for you.</p></body></html>',
                'text'        => 'This is a special message just for you.',
                'headers'     => [
                    'X-Custom: some-custom-header',
                ],
                'attachments' => [
                    ['type' => 'text/plain', 'name' => 'textfile.txt', 'data' => 'U29tZSB0ZXh0IGluIGEgZmlsZS4='],
                ],
            ],
            'campaign_id'       => 'some-campaign',
            'metadata'          => ['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur'],
            'substitution_data' => ['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper'],
            'options'           => [
                'transactional'    => false,
                'open_tracking'    => false,
                'click_tracking'   => false,
                'sandbox'          => true,
                'skip_suppression' => true,
                'inline_css'       => true,
                'ip_pool'          => 'some-ip-pool',
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_in_string_form()
    {
        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setReplyTo('noreply@domain.com')
            ->setTo('john@doe.com')
            ->setCc('jane@doe.com')
            ->setBcc('jake@doe.com');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
                ['address' => ['email' => 'jane@doe.com']],
                ['address' => ['email' => 'jake@doe.com', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => 'me@domain.com',
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_and_names_in_string_form()
    {
        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com', 'Me')
            ->setReplyTo('noreply@domain.com', 'No Reply')
            ->setTo('john@doe.com', 'John')
            ->setCc('jane@doe.com', 'Jane')
            ->setBcc('jake@doe.com', 'Jake');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com', 'name' => 'John']],
                ['address' => ['email' => 'jane@doe.com', 'name' => 'Jane']],
                ['address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_in_array_form()
    {
        $message = Swift_Message::newInstance()
            ->setFrom(['me@domain.com'])
            ->setReplyTo(['noreply@domain.com'])
            ->setTo(['john@doe.com'])
            ->setCc(['jane@doe.com'])
            ->setBcc(['jake@doe.com']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
                ['address' => ['email' => 'jane@doe.com']],
                ['address' => ['email' => 'jake@doe.com', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => 'me@domain.com',
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_builds_the_payload_with_addresses_and_names_in_array_form()
    {
        $message = Swift_Message::newInstance()
            ->setFrom(['me@domain.com' => 'Me'])
            ->setReplyTo(['noreply@domain.com' => 'No Reply'])
            ->setTo(['john@doe.com' => 'John'])
            ->setCc(['jane@doe.com' => 'Jane'])
            ->setBcc(['jake@doe.com' => 'Jake']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com', 'name' => 'John']],
                ['address' => ['email' => 'jane@doe.com', 'name' => 'Jane']],
                ['address' => ['email' => 'jake@doe.com', 'name' => 'Jake', 'header_to' => 'john@doe.com']],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_convertss_asterisk_pipe_variables_to_curly_braces()
    {
        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com'])
            ->setSubject('Hello there, *|NAME|*!')
            ->setBody(
                '<html><body><p>This is a special message just for you, *|NAME|*.</p></body></html>',
                'text/html'
            )
            ->addPart('This is a special message just for you, *|NAME|*.', 'text/plain');

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => 'Hello there, {{NAME}}!',
                'from'    => 'me@domain.com',
                'html'    => '<html><body><p>This is a special message just for you, {{NAME}}.</p></body></html>',
                'text'    => 'This is a special message just for you, {{NAME}}.',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $this->payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Cannot send message without a recipient address
     */
    public function it_does_not_accept_a_message_without_real_recipients()
    {
        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com');

        $this->payloadBuilder->buildPayload($message);
    }

    /**
     * @test
     */
    public function it_overrides_recipients_when_configured_to_do_so()
    {
        $config = Configuration::newInstance()
            ->setRecipientOverride('override@domain.com');

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com', 'Me')
            ->setReplyTo('noreply@domain.com', 'No Reply')
            ->setTo(['john@doe.com' => 'John', 'jack@doe.com' => 'Jack'])
            ->setCc(['jane@doe.com' => 'Jane', 'jamie@doe.com' => 'Jamie'])
            ->setBcc(['jake@doe.com' => 'Jake', 'joe@doe.com' => 'Joe']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'override@domain.com', 'name' => 'John']],

                ['address' => ['email' => 'override@domain.com', 'name' => 'Jack'],],
                ['address' => ['email' => 'override@domain.com', 'name' => 'Jane']],
                ['address' => ['email' => 'override@domain.com', 'name' => 'Jamie']],
                [
                    'address' => [
                        'email'     => 'override@domain.com',
                        'name'      => 'Jake',
                        'header_to' => 'override@domain.com',
                    ],
                ],
                [
                    'address' => [
                        'email'     => 'override@domain.com',
                        'name'      => 'Joe',
                        'header_to' => 'override@domain.com',
                    ],
                ],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_keeps_track_of_substitution_data_when_overriding_recipients()
    {
        $config = Configuration::newInstance()
            ->setRecipientOverride('override@domain.com');

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Message::newInstance();
        $message->setFrom('me@domain.com', 'Me');
        $message->setReplyTo('noreply@domain.com', 'No Reply');
        $message->setTo('john@doe.com', 'John');
        $message->setCc('jane@doe.com', 'Jane');
        $message->setBcc('jake@doe.com', 'Jake');
        $message->setPerRecipientTags('john@doe.com', ['eget', 'bibendum']);
        $message->setPerRecipientMetadata('jane@doe.com', ['adipiscing' => 'elit', 'donec' => 'vitae']);
        $message->setPerRecipientSubstitutionData('jake@doe.com', ['rutrum' => 'sed', 'vel' => 'nunc']);

        $expectedPayload = [
            'recipients' => [
                [
                    'address' => ['email' => 'override@domain.com', 'name' => 'John'],
                    'tags'    => ['eget', 'bibendum'],
                ],
                [
                    'address'  => ['email' => 'override@domain.com', 'name' => 'Jane'],
                    'metadata' => ['adipiscing' => 'elit', 'donec' => 'vitae'],
                ],
                [
                    'address'           => [
                        'email'     => 'override@domain.com',
                        'name'      => 'Jake',
                        'header_to' => 'override@domain.com',
                    ],
                    'substitution_data' => ['rutrum' => 'sed', 'vel' => 'nunc'],
                ],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_overrides_recipients_Gmail_style_when_configured_to_do_so()
    {
        $config = Configuration::newInstance()
            ->setRecipientOverride('override@domain.com')
            ->setOverrideGmailStyle(true);

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com', 'Me')
            ->setReplyTo('noreply@domain.com', 'No Reply')
            ->setTo(['john@doe.com' => 'John', 'jack@doe.com' => 'Jack'])
            ->setCc(['jane@doe.com' => 'Jane', 'jamie@doe.com' => 'Jamie'])
            ->setBcc(['jake@doe.com' => 'Jake', 'joe@doe.com' => 'Joe']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'override+john-doe-com@domain.com', 'name' => 'John']],

                ['address' => ['email' => 'override+jack-doe-com@domain.com', 'name' => 'Jack'],],
                ['address' => ['email' => 'override+jane-doe-com@domain.com', 'name' => 'Jane']],
                ['address' => ['email' => 'override+jamie-doe-com@domain.com', 'name' => 'Jamie']],
                [
                    'address' => [
                        'email'     => 'override+jake-doe-com@domain.com',
                        'name'      => 'Jake',
                        'header_to' => 'override+john-doe-com@domain.com',
                    ],
                ],
                [
                    'address' => [
                        'email'     => 'override+joe-doe-com@domain.com',
                        'name'      => 'Joe',
                        'header_to' => 'override+john-doe-com@domain.com',
                    ],
                ],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_keeps_track_of_substitution_data_when_overriding_recipients_Gmail_style()
    {
        $config = Configuration::newInstance()
            ->setRecipientOverride('override@domain.com')
            ->setOverrideGmailStyle(true);

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Message::newInstance();
        $message->setFrom('me@domain.com', 'Me');
        $message->setReplyTo('noreply@domain.com', 'No Reply');
        $message->setTo('john@doe.com', 'John');
        $message->setCc('jane@doe.com', 'Jane');
        $message->setBcc('jake@doe.com', 'Jake');
        $message->setPerRecipientTags('john@doe.com', ['eget', 'bibendum']);
        $message->setPerRecipientMetadata('jane@doe.com', ['adipiscing' => 'elit', 'donec' => 'vitae']);
        $message->setPerRecipientSubstitutionData('jake@doe.com', ['rutrum' => 'sed', 'vel' => 'nunc']);

        $expectedPayload = [
            'recipients' => [
                [
                    'address' => ['email' => 'override+john-doe-com@domain.com', 'name' => 'John'],
                    'tags'    => ['eget', 'bibendum'],
                ],
                [
                    'address'  => ['email' => 'override+jane-doe-com@domain.com', 'name' => 'Jane'],
                    'metadata' => ['adipiscing' => 'elit', 'donec' => 'vitae'],
                ],
                [
                    'address'           => [
                        'email'     => 'override+jake-doe-com@domain.com',
                        'name'      => 'Jake',
                        'header_to' => 'override+john-doe-com@domain.com',
                    ],
                    'substitution_data' => ['rutrum' => 'sed', 'vel' => 'nunc'],
                ],
            ],
            'content'    => [
                'subject'  => '',
                'from'     => ['email' => 'me@domain.com', 'name' => 'Me'],
                'reply_to' => 'noreply@domain.com',
                'text'     => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_uses_configured_options()
    {
        $config = Configuration::newInstance()
            ->setOptions(
                [
                    Configuration::OPT_TRANSACTIONAL    => false,
                    Configuration::OPT_OPEN_TRACKING    => false,
                    Configuration::OPT_CLICK_TRACKING   => false,
                    Configuration::OPT_SANDBOX          => true,
                    Configuration::OPT_SKIP_SUPPRESSION => true,
                    Configuration::OPT_INLINE_CSS       => true,
                    Configuration::OPT_IP_POOL          => 'some-ip-pool',
                ]
            );

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional'    => false,
                'open_tracking'    => false,
                'click_tracking'   => false,
                'sandbox'          => true,
                'skip_suppression' => true,
                'inline_css'       => true,
                'ip_pool'          => 'some-ip-pool',
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_is_not_affected_by_changes_to_configuration_after_being_supplied()
    {
        $config = Configuration::newInstance();

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $config
            ->setRecipientOverride('override@domain.com')
            ->setOptions([Configuration::OPT_TRANSACTIONAL => false]);

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com']);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_overrides_configured_options_with_message_options()
    {
        $config = Configuration::newInstance()
            ->setOptions(
                [
                    Configuration::OPT_TRANSACTIONAL    => false,
                    Configuration::OPT_OPEN_TRACKING    => false,
                    Configuration::OPT_CLICK_TRACKING   => false,
                    Configuration::OPT_SANDBOX          => true,
                    Configuration::OPT_SKIP_SUPPRESSION => true,
                    Configuration::OPT_INLINE_CSS       => true,
                    Configuration::OPT_IP_POOL          => 'some-ip-pool',
                ]
            );

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com'])
            ->setOptions(
                [
                    Configuration::OPT_TRANSACTIONAL    => true,
                    Configuration::OPT_OPEN_TRACKING    => true,
                    Configuration::OPT_CLICK_TRACKING   => true,
                    Configuration::OPT_SANDBOX          => false,
                    Configuration::OPT_SKIP_SUPPRESSION => false,
                    Configuration::OPT_INLINE_CSS       => false,
                    Configuration::OPT_IP_POOL          => 'other-ip-pool',
                ]
            );

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional'    => true,
                'open_tracking'    => true,
                'click_tracking'   => true,
                'sandbox'          => false,
                'skip_suppression' => false,
                'inline_css'       => false,
                'ip_pool'          => 'other-ip-pool',
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_does_not_use_the_ip_pool_if_probability_is_0()
    {
        $config = Configuration::newInstance()
            ->setOptions([Configuration::OPT_IP_POOL => 'some-ip-pool'])
            ->setIpPoolProbability(0);

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com']);

        $this->randomNumberGenerator->generate()
            ->shouldNotBeCalled();

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_does_not_use_the_ip_pool_if_random_number_is_higher_than_probability()
    {
        $config = Configuration::newInstance()
            ->setOptions([Configuration::OPT_IP_POOL => 'some-ip-pool'])
            ->setIpPoolProbability(0.5);

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com']);

        $this->randomNumberGenerator->generate()
            ->willReturn(0.6);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional' => true,
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }

    /**
     * @test
     */
    public function it_does_use_the_ip_pool_if_random_number_is_lower_than_probability()
    {
        $config = Configuration::newInstance()
            ->setOptions([Configuration::OPT_IP_POOL => 'some-ip-pool'])
            ->setIpPoolProbability(0.5);

        $payloadBuilder = new StandardPayloadBuilder(
            $config,
            $this->randomNumberGenerator->reveal()
        );

        $message = Swift_Message::newInstance()
            ->setFrom('me@domain.com')
            ->setTo(['john@doe.com']);

        $this->randomNumberGenerator->generate()
            ->willReturn(0.4);

        $expectedPayload = [
            'recipients' => [
                ['address' => ['email' => 'john@doe.com']],
            ],
            'content'    => [
                'subject' => '',
                'from'    => 'me@domain.com',
                'text'    => '',
            ],
            'options'    => [
                'transactional' => true,
                'ip_pool'       => 'some-ip-pool',
            ],
        ];

        $actualPayload = $payloadBuilder->buildPayload($message);

        $this->assertSame($expectedPayload, $actualPayload);
    }
}
