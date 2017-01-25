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
    const OPT_TRANSACTIONAL    = 'transactional';
    const OPT_OPEN_TRACKING    = 'open_tracking';
    const OPT_CLICK_TRACKING   = 'click_tracking';
    const OPT_SANDBOX          = 'sandbox';
    const OPT_SKIP_SUPPRESSION = 'skip_suppression';
    const OPT_INLINE_CSS       = 'inline_css';
    const OPT_IP_POOL          = 'ip_pool';

    /**
     * @var string
     */
    private $recipientOverride;

    /**
     * @var bool
     */
    private $overrideGmailStyle;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     *
     * @throws Exception
     */
    public static function guardOptionValidity(array $options)
    {
        $validOptions = [
            self::OPT_TRANSACTIONAL,
            self::OPT_OPEN_TRACKING,
            self::OPT_CLICK_TRACKING,
            self::OPT_SANDBOX,
            self::OPT_SKIP_SUPPRESSION,
            self::OPT_INLINE_CSS,
            self::OPT_IP_POOL,
        ];

        foreach (array_keys($options) as $option) {
            if (!in_array($option, $validOptions, true)) {
                throw new Exception(sprintf('Unknown SparkPost option "%s"', $option));
            }
        }
    }

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
        $this->options            = [self::OPT_TRANSACTIONAL => true];
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

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return Configuration
     * @throws Exception
     */
    public function setOptions(array $options)
    {
        self::guardOptionValidity($options);

        foreach ($options as $option => $value) {
            if ($option === self::OPT_IP_POOL) {
                $this->options[$option] = (string) $value;
                continue;
            }

            $this->options[$option] = (bool) $value;
        }

        return $this;
    }
}
