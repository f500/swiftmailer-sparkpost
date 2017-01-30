<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE Proprietary
 */

namespace SwiftSparkPost;

use Swift_Mime_Message;
use Swift_OutputByteStream;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
interface ExtendedMessage extends Swift_Mime_Message
{
    /**
     * @return string
     */
    public function getCampaignId();

    /**
     * @param string $campaignId
     *
     * @return static
     */
    public function setCampaignId($campaignId);

    /**
     * @return array
     */
    public function getPerRecipientTags();

    /**
     * @param string $recipient
     * @param array  $tags
     *
     * @return static
     */
    public function setPerRecipientTags($recipient, array $tags);

    /**
     * @return array
     */
    public function getMetadata();

    /**
     * @param array $metadata
     *
     * @return static
     */
    public function setMetadata(array $metadata);

    /**
     * @return array
     */
    public function getPerRecipientMetadata();

    /**
     * @param string $recipient
     * @param array  $metadata
     *
     * @return static
     */
    public function setPerRecipientMetadata($recipient, array $metadata);

    /**
     * @return array
     */
    public function getSubstitutionData();

    /**
     * @param array $substitutionData
     *
     * @return static
     */
    public function setSubstitutionData(array $substitutionData);

    /**
     * @return array
     */
    public function getPerRecipientSubstitutionData();

    /**
     * @param string $recipient
     * @param array  $substitutionData
     *
     * @return static
     */
    public function setPerRecipientSubstitutionData($recipient, array $substitutionData);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions(array $options);

    /**
     * @param string|Swift_OutputByteStream $body
     * @param string|null                   $contentType
     * @param string|null                   $charset
     *
     * @return static
     */
    public function addPart($body, $contentType = null, $charset = null);
}
