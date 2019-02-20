<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit_Framework_TestCase;
use stdClass;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use SwiftSparkPost\Message;
use SwiftSparkPost\Option;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class MessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var resource|null
     */
    private $file;

    protected function tearDown()
    {
        if ($this->file) {
            fclose($this->file);
        }
    }

    /**
     * @test
     */
    public function it_is_an_extension_of_a_Swift_Message()
    {
        $message = new Message();

        $this->assertInstanceOf(Swift_Mime_SimpleMessage::class, $message);
        $this->assertInstanceOf(Swift_Message::class, $message);
    }

    /**
     * @test
     */
    public function it_can_be_created_statically()
    {
        $message = Message::newInstance();

        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * @test
     */
    public function its_additional_properties_are_empty_by_default()
    {
        $message = new Message();

        $this->assertSame('', $message->getCampaignId());
        $this->assertSame([], $message->getPerRecipientTags());
        $this->assertSame([], $message->getMetadata());
        $this->assertSame([], $message->getPerRecipientMetadata());
        $this->assertSame([], $message->getSubstitutionData());
        $this->assertSame([], $message->getPerRecipientSubstitutionData());
        $this->assertSame([], $message->getOptions());
    }

    /**
     * @test
     */
    public function it_has_a_CampaignId_when_provided()
    {
        $message = new Message();
        $message->setCampaignId('some-campaign');

        $this->assertSame('some-campaign', $message->getCampaignId());
    }

    /**
     * @test
     */
    public function it_has_Tags_when_provided()
    {
        $message = new Message();

        $message->setPerRecipientTags('john@doe.com', ['eget', 'bibendum']);
        $message->setPerRecipientTags('jane@doe.com', ['nunc']);

        $this->assertSame(
            [
                'john@doe.com' => ['eget', 'bibendum'],
                'jane@doe.com' => ['nunc'],
            ],
            $message->getPerRecipientTags()
        );
    }

    /**
     * @test
     */
    public function it_has_Metadata_when_provided()
    {
        $message = new Message();
        $message->setMetadata(['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur']);
        $message->setPerRecipientMetadata('john@doe.com', ['adipiscing' => 'elit', 'donec' => 'vitae']);
        $message->setPerRecipientMetadata('jane@doe.com', ['arcu' => 'non']);

        $this->assertSame(
            ['lorem' => 'ipsum', 'dolor' => 'sit', 'amet' => 'consectetur'],
            $message->getMetadata()
        );

        $this->assertSame(
            [
                'john@doe.com' => ['adipiscing' => 'elit', 'donec' => 'vitae'],
                'jane@doe.com' => ['arcu' => 'non'],
            ],
            $message->getPerRecipientMetadata()
        );
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Metadata cannot contain objects or resources
     */
    public function it_prevents_objects_being_used_as_Metadata()
    {
        $message = new Message();
        $message->setMetadata(['object' => new stdClass()]);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Metadata cannot contain objects or resources
     */
    public function it_prevents_objects_being_used_as_PerRecipientMetadata()
    {
        $message = new Message();
        $message->setPerRecipientMetadata('john@doe.com', ['object' => new stdClass()]);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Metadata cannot contain objects or resources
     */
    public function it_prevents_resources_being_used_as_Metadata()
    {
        $this->file = fopen('php://memory', 'r');

        $message = new Message();
        $message->setMetadata(['resource' => $this->file]);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Metadata cannot contain objects or resources
     */
    public function it_prevents_resources_being_used_as_PerRecipientMetadata()
    {
        $this->file = fopen('php://memory', 'r');

        $message = new Message();
        $message->setPerRecipientMetadata('john@doe.com', ['resource' => $this->file]);
    }

    /**
     * @test
     */
    public function it_has_SubstitutionData_when_provided()
    {
        $message = new Message();

        $message->setSubstitutionData(['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper']);
        $message->setPerRecipientSubstitutionData('john@doe.com', ['rutrum' => 'sed', 'vel' => 'nunc']);
        $message->setPerRecipientSubstitutionData('jane@doe.com', ['mollis' => 'luctus']);

        $this->assertSame(
            ['aenean' => 'pretium', 'sapien' => 'nec', 'eros' => 'ullamcorper'],
            $message->getSubstitutionData()
        );

        $this->assertSame(
            [
                'john@doe.com' => ['rutrum' => 'sed', 'vel' => 'nunc'],
                'jane@doe.com' => ['mollis' => 'luctus'],

            ],
            $message->getPerRecipientSubstitutionData()
        );
    }

    /**
     * @test
     */
    public function it_has_Options_when_provided()
    {
        $options = [
            Option::TRANSACTIONAL    => false,
            Option::OPEN_TRACKING    => false,
            Option::CLICK_TRACKING   => false,
            Option::SANDBOX          => true,
            Option::SKIP_SUPPRESSION => true,
            Option::INLINE_CSS       => true,
            Option::IP_POOL          => 'some-ip-pool',
        ];

        $message = new Message();
        $message->setOptions($options);

        $this->assertSame($options, $message->getOptions());
    }

    /**
     * @test
     */
    public function it_filters_out_an_empty_ip_pool()
    {
        $message = new Message();
        $message->setOptions([Option::IP_POOL => null]);

        $this->assertSame([], $message->getOptions());
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Unknown SparkPost option "unknown_option"
     */
    public function it_does_not_accept_an_unknown_option()
    {
        $message = new Message();
        $message->setOptions(['unknown_option' => 'ullamcorper']);
    }
}
