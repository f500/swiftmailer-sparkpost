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
    public static function newInstance(): Configuration
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

    public function overrideRecipients(): bool
    {
        return $this->recipientOverride !== '';
    }

    /**
     * @return bool
     */
    public function overrideGmailStyle(): bool
    {
        return $this->overrideGmailStyle;
    }

    /**
     * @param bool $overrideGmailStyle
     *
     * @return Configuration
     */
    public function setOverrideGmailStyle(bool $overrideGmailStyle): Configuration
    {
        $this->overrideGmailStyle = (bool) $overrideGmailStyle;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipientOverride(): string
    {
        return $this->recipientOverride;
    }

    /**
     * @param string|null $recipientOverride
     *
     * @return Configuration
     * @throws Exception
     */
    public function setRecipientOverride(?string $recipientOverride): Configuration
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
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return Configuration
     */
    public function setOptions(array $options): Configuration
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
    public function getIpPoolProbability(): float
    {
        return $this->ipPoolProbability;
    }

    /**
     * @param float $ipPoolProbability
     *
     * @return Configuration
     * @throws Exception
     */
    public function setIpPoolProbability(float $ipPoolProbability): Configuration
    {
        if ($ipPoolProbability < 0 || $ipPoolProbability > 1) {
            throw new Exception('IP pool probability must be between 0 and 1');
        }

        $this->ipPoolProbability = (float) $ipPoolProbability;

        return $this;
    }
}
