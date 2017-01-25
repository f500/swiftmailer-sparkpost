<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

use Swift_Mime_Message;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
interface PayloadBuilder
{
    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    public function buildPayload(Swift_Mime_Message $message);
}
