<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
interface RandomNumberGenerator
{
    /**
     * Returns a random number between 0 and 1.
     *
     * @return float
     */
    public function generate(): float;
}
