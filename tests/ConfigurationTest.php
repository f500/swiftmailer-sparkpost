<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit_Framework_TestCase;
use SwiftSparkPost\Configuration;
use SwiftSparkPost\Option;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_represents_configuration_with_sane_defaults()
    {
        $config = new Configuration();

        $this->assertSame(false, $config->overrideRecipients());
        $this->assertSame(false, $config->overrideGmailStyle());
        $this->assertSame('', $config->getRecipientOverride());
        $this->assertSame(1.0, $config->getIpPoolProbability());
    }

    /**
     * @test
     */
    public function it_states_that_messages_are_transactional_by_default()
    {
        $config = new Configuration();

        $this->assertSame(
            [Option::TRANSACTIONAL => true],
            $config->getOptions()
        );
    }

    /**
     * @test
     */
    public function it_can_be_created_statically()
    {
        $config = Configuration::newInstance();

        $this->assertInstanceOf(Configuration::class, $config);
    }

    /**
     * @test
     */
    public function it_states_that_recipients_should_be_overridden_when_an_override_is_provided()
    {
        $config = Configuration::newInstance()
            ->setRecipientOverride('override@domain.com');

        $this->assertSame(true, $config->overrideRecipients());
        $this->assertSame('override@domain.com', $config->getRecipientOverride());
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Recipient override must be a valid email address
     */
    public function it_does_not_accept_an_invalid_recipient_override()
    {
        Configuration::newInstance()
            ->setRecipientOverride('invalid email');
    }

    /**
     * @test
     */
    public function it_states_that_Gmail_style_overriding_should_be_done_when_configured_so()
    {
        $config = Configuration::newInstance()
            ->setOverrideGmailStyle(true);

        $this->assertSame(true, $config->overrideGmailStyle());
    }

    /**
     * @test
     */
    public function it_exposes_options_when_provided()
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

        $config = Configuration::newInstance()
            ->setOptions($options);

        $this->assertSame($options, $config->getOptions());
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage Unknown SparkPost option "unknown_option"
     */
    public function it_does_not_accept_an_unknown_option()
    {
        Configuration::newInstance()
            ->setOptions(['unknown_option' => 'ullamcorper']);
    }

    /**
     * @test
     */
    public function it_filters_out_an_empty_ip_pool()
    {
        $config = Configuration::newInstance()
            ->setOptions([Option::IP_POOL => null]);

        $this->assertSame(['transactional' => true], $config->getOptions());
    }

    /**
     * @test
     */
    public function it_exposes_the_ip_pool_probability_when_provided()
    {
        $config = Configuration::newInstance()
            ->setIpPoolProbability(0.5);

        $this->assertSame(0.5, $config->getIpPoolProbability());
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage IP pool probability must be between 0 and 1
     */
    public function it_does_not_accept_an_ip_pool_probability_lower_than_0()
    {
        Configuration::newInstance()
            ->setIpPoolProbability(-0.1);
    }

    /**
     * @test
     * @expectedException \SwiftSparkPost\Exception
     * @expectedExceptionMessage IP pool probability must be between 0 and 1
     */
    public function it_does_not_accept_an_ip_pool_probability_higher_than_1()
    {
        Configuration::newInstance()
            ->setIpPoolProbability(1.1);
    }
}
