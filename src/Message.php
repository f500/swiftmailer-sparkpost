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
final class Message extends Swift_Message
{
    use OptionsSanitizingCapabilities;

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
     * @return string
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param string $campaignId
     *
     * @return Message
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * @return array
     */
    public function getPerRecipientTags()
    {
        return $this->perRecipientTags;
    }

    /**
     * @param string $recipient
     * @param array  $tags
     *
     * @return Message
     */
    public function setPerRecipientTags($recipient, array $tags)
    {
        $this->perRecipientTags[(string) $recipient] = $this->sanitizeTags($tags);

        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     *
     * @return Message
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * @return array
     */
    public function getPerRecipientMetadata()
    {
        return $this->perRecipientMetadata;
    }

    /**
     * @param string $recipient
     * @param array  $metadata
     *
     * @return Message
     */
    public function setPerRecipientMetadata($recipient, array $metadata)
    {
        $this->perRecipientMetadata[(string) $recipient] = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * @return array
     */
    public function getSubstitutionData()
    {
        return $this->substitutionData;
    }

    /**
     * @param array $substitutionData
     *
     * @return Message
     */
    public function setSubstitutionData(array $substitutionData)
    {
        $this->substitutionData = $this->sanitizeSubstitutionData($substitutionData);

        return $this;
    }

    /**
     * @return array
     */
    public function getPerRecipientSubstitutionData()
    {
        return $this->perRecipientSubstitutionData;
    }

    /**
     * @param string $recipient
     * @param array  $substitutionData
     *
     * @return Message
     */
    public function setPerRecipientSubstitutionData($recipient, array $substitutionData)
    {
        $this->perRecipientSubstitutionData[(string) $recipient] = $this->sanitizeSubstitutionData($substitutionData);

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
     * @return Message
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
     * @param array $tags
     *
     * @return array
     */
    private function sanitizeTags(array $tags)
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
    private function sanitizeMetadata(array $metadata)
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
