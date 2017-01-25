<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

use Swift_Attachment;
use Swift_Mime_Header;
use Swift_Mime_Message;
use Swift_MimePart;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class PayloadBuilder implements PayloadBuilderInterface
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = clone $config;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    public function buildPayload(Swift_Mime_Message $message)
    {
        $payload = [
            'recipients' => $this->buildRecipients($message),
            'content'    => $this->buildContent($message),
        ];

        if ($campaignId = $this->buildCampaignId($message)) {
            $payload['campaign_id'] = $campaignId;
        }

        if ($metadata = $this->buildMetadata($message)) {
            $payload['metadata'] = $metadata;
        }

        if ($substitutionData = $this->buildSubstitutionData($message)) {
            $payload['substitution_data'] = $substitutionData;
        }

        if ($options = $this->buildOptions($message)) {
            $payload['options'] = $options;
        }

        return $payload;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     * @throws Exception
     */
    private function buildRecipients(Swift_Mime_Message $message)
    {
        $tos = array_merge((array) $message->getTo(), (array) $message->getCc());
        $bcc = (array) $message->getBcc();

        if (count($tos) === 0) {
            throw new Exception('Cannot send message without a recipient address');
        }

        $tags             = [];
        $metadata         = [];
        $substitutionData = [];

        if ($message instanceof Message) {
            $tags             = $message->getPerRecipientTags();
            $metadata         = $message->getPerRecipientMetadata();
            $substitutionData = $message->getPerRecipientSubstitutionData();
        }

        $recipients = [];

        foreach ($tos as $email => $name) {
            $recipients[] = $this->buildRecipient($email, $name, $tags, $metadata, $substitutionData);
        }

        $originalEmail = current($recipients)['address']['email'];

        foreach ($bcc as $email => $name) {
            $recipients[] = $this->buildRecipient($email, $name, $tags, $metadata, $substitutionData, $originalEmail);
        }

        return $recipients;
    }

    /**
     * @param string $email
     * @param string $name
     * @param array  $tags
     * @param array  $metadata
     * @param array  $substitutionData
     * @param string $originalEmail
     *
     * @return array
     */
    private function buildRecipient(
        $email,
        $name,
        array $tags,
        array $metadata,
        array $substitutionData,
        $originalEmail = ''
    ) {
        $recipient = ['address' => ['email' => $this->overrideRecipient($email)]];

        if ($name) {
            $recipient['address']['name'] = $name;
        }

        if ($originalEmail) {
            $recipient['address']['header_to'] = $originalEmail;
        }

        if (isset($tags[$email])) {
            $recipient['tags'] = $tags[$email];
        }

        if (isset($metadata[$email])) {
            $recipient['metadata'] = $metadata[$email];
        }

        if (isset($substitutionData[$email])) {
            $recipient['substitution_data'] = $substitutionData[$email];
        }

        return $recipient;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildContent(Swift_Mime_Message $message)
    {
        $content = [
            'subject' => $this->convertAsteriskPipeToCurlyBraces($message->getSubject()),
            'from'    => $this->buildFrom($message),
        ];

        if ($message->getReplyTo()) {
            $content['reply_to'] = key($message->getReplyTo());
        }

        $contentMap = ['text/html' => 'html', 'text/plain' => 'text'];

        $contentType = $this->readUserContentType($message);
        if (isset($contentMap[$contentType])) {
            $content[$contentMap[$contentType]] = $this->convertAsteriskPipeToCurlyBraces($message->getBody());
        }

        foreach ($message->getChildren() as $part) {
            if (!($part instanceof Swift_MimePart)) {
                continue;
            }

            $contentType = $part->getContentType();
            if (isset($contentMap[$contentType])) {
                $content[$contentMap[$contentType]] = $this->convertAsteriskPipeToCurlyBraces($part->getBody());
            }
        }

        if ($headers = $this->buildHeaders($message)) {
            $content['headers'] = $headers;
        }

        if ($attachments = $this->buildAttachments($message)) {
            $content['attachments'] = $attachments;
        }

        return $content;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array|string
     */
    private function buildFrom(Swift_Mime_Message $message)
    {
        $from = $message->getFrom();

        if (current($from)) {
            return ['email' => key($from), 'name' => current($from)];
        }

        return key($from);
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildHeaders(Swift_Mime_Message $message)
    {
        $headers = [];
        $filter  = [
            'Bcc',
            'Cc',
            'Content-Transfer-Encoding',
            'Content-Type',
            'Date',
            'DKIM-Signature',
            'DomainKey-Signature',
            'From',
            'Message-ID',
            'MIME-Version',
            'Received',
            'Reply-To',
            'Return-Path',
            'Sender',
            'Subject',
            'To',
        ];

        /** @var Swift_Mime_Header $header */
        foreach ($message->getHeaders()->getAll() as $header) {
            if (in_array($header->getFieldName(), $filter, true)) {
                continue;
            }

            $headers[] = trim($header->toString());
        }

        return $headers;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildAttachments(Swift_Mime_Message $message)
    {
        $attachments = [];

        foreach ($message->getChildren() as $part) {
            if ($part instanceof Swift_Attachment) {
                $attachments[] = [
                    'type' => $part->getContentType(),
                    'name' => $part->getFilename(),
                    'data' => base64_encode($part->getBody()),
                ];
            }
        }

        return $attachments;
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return string
     */
    private function buildCampaignId(Swift_Mime_Message $message)
    {
        if (!($message instanceof Message)) {
            return '';
        }

        return $message->getCampaignId();
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildMetadata(Swift_Mime_Message $message)
    {
        if (!($message instanceof Message)) {
            return [];
        }

        return $message->getMetadata();
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildSubstitutionData(Swift_Mime_Message $message)
    {
        if (!($message instanceof Message)) {
            return [];
        }

        return $message->getSubstitutionData();
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    private function buildOptions(Swift_Mime_Message $message)
    {
        $options = $this->config->getOptions();

        if ($message instanceof Message) {
            $options = array_merge($options, $message->getOptions());
        }

        return $options;
    }

    /**
     * Convert *|foo|* to {{foo}}
     *
     * @param string
     *
     * @return string
     */
    private function convertAsteriskPipeToCurlyBraces($content)
    {
        return preg_replace('/\*\|(.+?)\|\*/', '{{\1}}', $content);
    }

    /**
     * @param Swift_Mime_Message $message
     *
     * @return string
     */
    private function readUserContentType(Swift_Mime_Message $message)
    {
        $ro = new \ReflectionObject($message);
        $rp = $ro->getProperty('_userContentType');
        $rp->setAccessible(true);
        return (string) $rp->getValue($message);
    }

    /**
     * @param string $email
     *
     * @return string
     */
    private function overrideRecipient($email)
    {
        if (!$this->config->overrideRecipients()) {
            return $email;
        }

        if (!$this->config->overrideGmailStyle()) {
            return $this->config->getRecipientOverride();
        }

        list ($userPart, $domainPart) = explode('@', $this->config->getRecipientOverride());
        $reformattedEmail = trim(preg_replace('/([^a-z0-9]+)/i', '-', $email), '-');

        return sprintf('%s+%s@%s', $userPart, $reformattedEmail, $domainPart);
    }
}
