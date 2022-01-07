<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
trait OptionsSanitizingCapabilities
{
    /**
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    private function sanitizeOptions(array $options): array
    {
        $booleanOptions = [
            Option::TRANSACTIONAL,
            Option::OPEN_TRACKING,
            Option::CLICK_TRACKING,
            Option::SANDBOX,
            Option::SKIP_SUPPRESSION,
            Option::INLINE_CSS,
        ];
        $stringOptions  = [
            Option::IP_POOL,
        ];

        $sanitized = [];

        foreach ($options as $option => $value) {
            if (in_array($option, $stringOptions, true)) {
                if ($value) {
                    $sanitized[$option] = (string) $value;
                }
                continue;
            }

            if (in_array($option, $booleanOptions, true)) {
                $sanitized[$option] = (bool) $value;
                continue;
            }

            throw new Exception(sprintf('Unknown SparkPost option "%s"', $option));
        }

        return $sanitized;
    }
}
