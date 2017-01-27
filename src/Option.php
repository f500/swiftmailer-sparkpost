<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class Option
{
    const TRANSACTIONAL    = 'transactional';
    const OPEN_TRACKING    = 'open_tracking';
    const CLICK_TRACKING   = 'click_tracking';
    const SANDBOX          = 'sandbox';
    const SKIP_SUPPRESSION = 'skip_suppression';
    const INLINE_CSS       = 'inline_css';
    const IP_POOL          = 'ip_pool';
}
