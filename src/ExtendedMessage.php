<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE Proprietary
 */

namespace SwiftSparkPost;

use Swift_OutputByteStream;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
interface ExtendedMessage
{
    /**
     * @return int
     */
    public function getTransmissionId(): int;

    /**
     * @param int $transmissionId
     * @return static
     */
    public function setTransmissionId(int $transmissionId): ExtendedMessage;

    /**
     * @return string
     */
    public function getCampaignId(): string;

    /**
     * @param string $campaignId
     *
     * @return static
     */
    public function setCampaignId(string $campaignId): ExtendedMessage;

    /**
     * @return array
     */
    public function getPerRecipientTags(): array;

    /**
     * @param string $recipient
     * @param array  $tags
     *
     * @return static
     */
    public function setPerRecipientTags(string $recipient, array $tags): ExtendedMessage;

    /**
     * @return array
     */
    public function getMetadata(): array;

    /**
     * @param array $metadata
     *
     * @return static
     */
    public function setMetadata(array $metadata): ExtendedMessage;

    /**
     * @return array
     */
    public function getPerRecipientMetadata(): array;

    /**
     * @param string $recipient
     * @param array  $metadata
     *
     * @return static
     */
    public function setPerRecipientMetadata(string $recipient, array $metadata): ExtendedMessage;

    /**
     * @return array
     */
    public function getSubstitutionData(): array;

    /**
     * @param array $substitutionData
     *
     * @return static
     */
    public function setSubstitutionData(array $substitutionData): ExtendedMessage;

    /**
     * @return array
     */
    public function getPerRecipientSubstitutionData(): array;

    /**
     * @param string $recipient
     * @param array  $substitutionData
     *
     * @return static
     */
    public function setPerRecipientSubstitutionData(string $recipient, array $substitutionData): ExtendedMessage;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions(array $options): ExtendedMessage;

    /**
     * @param string|Swift_OutputByteStream $body
     * @param string|null $contentType
     * @param string|null $charset
     *
     * @return static
     */
    public function addPart($body, string $contentType = null, string $charset = null);
}
