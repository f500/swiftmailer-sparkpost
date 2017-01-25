<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE Proprietary
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class Configuration
{
    /**
     * @var string
     */
    private $recipientOverride;

    /**
     * @var bool
     */
    private $overrideGmailStyle;

    /**
     * @return Configuration
     */
    public static function newInstance()
    {
        return new self();
    }

    public function __construct()
    {
        $this->recipientOverride  = '';
        $this->overrideGmailStyle = false;
    }

    public function overrideRecipients()
    {
        return $this->recipientOverride !== '';
    }

    /**
     * @return bool
     */
    public function overrideGmailStyle()
    {
        return $this->overrideGmailStyle;
    }

    /**
     * @param bool $overrideGmailStyle
     *
     * @return Configuration
     */
    public function setOverrideGmailStyle($overrideGmailStyle)
    {
        $this->overrideGmailStyle = (bool) $overrideGmailStyle;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipientOverride()
    {
        return $this->recipientOverride;
    }

    /**
     * @param string $recipientOverride
     *
     * @return Configuration
     * @throws Exception
     */
    public function setRecipientOverride($recipientOverride)
    {
        if (!filter_var($recipientOverride, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Recipient override must be a valid email address');
        }

        $this->recipientOverride = (string) $recipientOverride;

        return $this;
    }
}
