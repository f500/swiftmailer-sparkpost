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
class Message extends Swift_Message implements ExtendedMessage
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
     * {@inheritdoc}
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * {@inheritdoc}
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientTags()
    {
        return $this->perRecipientTags;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientTags($recipient, array $tags)
    {
        $this->perRecipientTags[(string) $recipient] = $this->sanitizeTags($tags);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientMetadata()
    {
        return $this->perRecipientMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientMetadata($recipient, array $metadata)
    {
        $this->perRecipientMetadata[(string) $recipient] = $this->sanitizeMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubstitutionData()
    {
        return $this->substitutionData;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubstitutionData(array $substitutionData)
    {
        $this->substitutionData = $this->sanitizeSubstitutionData($substitutionData);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerRecipientSubstitutionData()
    {
        return $this->perRecipientSubstitutionData;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerRecipientSubstitutionData($recipient, array $substitutionData)
    {
        $this->perRecipientSubstitutionData[(string) $recipient] = $this->sanitizeSubstitutionData($substitutionData);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
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
