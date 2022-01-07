<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class MtRandomNumberGenerator implements RandomNumberGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(): float
    {
        return (mt_rand() / mt_getrandmax());
    }
}
