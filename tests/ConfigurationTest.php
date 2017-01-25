<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit_Framework_TestCase;
use SwiftSparkPost\Configuration;

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
}
