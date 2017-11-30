<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE Proprietary
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
class Configuration
{
    use OptionsSanitizingCapabilities;

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
     * @var float
     */
    private $ipPoolProbability;

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
        $this->options            = [Option::TRANSACTIONAL => true];
        $this->ipPoolProbability  = 1.0;
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
        if (!$recipientOverride) {
            return $this;
        }

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
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge(
            $this->options,
            $this->sanitizeOptions($options)
        );

        return $this;
    }

    /**
     * @return float
     */
    public function getIpPoolProbability()
    {
        return $this->ipPoolProbability;
    }

    /**
     * @param float $ipPoolProbability
     *
     * @return Configuration
     * @throws Exception
     */
    public function setIpPoolProbability($ipPoolProbability)
    {
        if ($ipPoolProbability < 0 || $ipPoolProbability > 1) {
            throw new Exception('IP pool probability must be between 0 and 1');
        }

        $this->ipPoolProbability = (float) $ipPoolProbability;

        return $this;
    }
}
