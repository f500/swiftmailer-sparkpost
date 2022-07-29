<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE Proprietary
 */

namespace SwiftSparkPost;

use Swift_Message;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class Message extends Swift_Message implements ExtendedMessage
{
    use OptionsSanitizingCapabilities;

    /**
     * @var int
     */
    private $transmissionId = 0;

    /**
     * @var string
     */
    private $campaignId;

    /**
     * @var array
     */
    private $perRecipientTags;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var array
     */
    private $perRecipientMetadata;

    /**
     * @var array
     */
    private $substitutionData;

    /**
     * @var array
     */
    private $perRecipientSubstitutionData;

    /**
     * @var array
     */
    private $options;

    /**
     * {@inheritdoc}
     */
    public static function newInstance($subject = null, $body = null, $contentType = null, $charset = null)
    {
        return new self($subject, $body, $contentType, $charset);
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);

        $this->campaignId                   = '';
        $this->perRecipientTags             = [];
        $this->metadata                     = [];
        $this->perRecipientMetadata         = [];
        $this->substitutionData             = [];
        $this->perRecipientSubstitutionData = [];
        $this->options                      = [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransmissionId(): int
    {
        return $this->transmissionId;
    }

    /**
     * {@inheritDoc}
     */
    public function setTransmissionId(int $transmissionId): ExtendedMessage
    {
        $this->transmissionId = $transmissionId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    /**
     * {@inheritdoc}
     */
    public function setCampaignId(string $campaignId): ExtendedMessage
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientTags(): array
    {
        return $this->perRecipientTags;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientTags(string $recipient, array $tags): ExtendedMessage
    {
        $this->perRecipientTags[(string) $recipient] = $this->sanitizeTags($tags);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $metadata): ExtendedMessage
    {
        $this->metadata = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientMetadata(): array
    {
        return $this->perRecipientMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientMetadata(string $recipient, array $metadata): ExtendedMessage
    {
        $this->perRecipientMetadata[(string) $recipient] = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstitutionData(): array
    {
        return $this->substitutionData;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubstitutionData(array $substitutionData): ExtendedMessage
    {
        $this->substitutionData = $this->sanitizeSubstitutionData($substitutionData);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientSubstitutionData(): array
    {
        return $this->perRecipientSubstitutionData;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientSubstitutionData(string $recipient, array $substitutionData): ExtendedMessage
    {
        $this->perRecipientSubstitutionData[(string) $recipient] = $this->sanitizeSubstitutionData($substitutionData);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): ExtendedMessage
    {
        $this->options = array_merge(
            $this->options,
            $this->sanitizeOptions($options)
        );

        return $this;
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    private function sanitizeTags(array $tags): array
    {
        $sanitized = [];

        foreach ($tags as $tag) {
            $sanitized[] = (string) $tag;
        }

        return $sanitized;
    }

    /**
     * @param array $metadata
     *
     * @return array
     */
    private function sanitizeMetadata(array $metadata): array
    {
        array_walk_recursive(
            $metadata,
            function ($value) {
                if (is_object($value) || is_resource($value)) {
                    throw new Exception('Metadata cannot contain objects or resources');
                }
            }
        );

        return $metadata;
    }

    /**
     * @param array $substitutionData
     *
     * @return array
     */
    private function sanitizeSubstitutionData(array $substitutionData)
    {
        $sanitized = [];

        foreach ($substitutionData as $key => $value) {
            $sanitized[(string) $key] = (string) $value;
        }

        return $sanitized;
    }
}
