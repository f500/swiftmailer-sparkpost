<?php

/**
 * @license https://github.com/f500/swiftmailer-sparkpost/blob/master/LICENSE MIT
 */

namespace SwiftSparkPost\Tests;

use PHPUnit\Framework\TestCase;
use SwiftSparkPost\MtRandomNumberGenerator;

/**
 * @copyright Future500 B.V.
 * @author    Jasper N. Brouwer <jasper@future500.nl>
 */
final class MtRandomNumberGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_a_number_between_0_and_1()
    {
        $randomNumberGenerator = new MtRandomNumberGenerator();

        for ($i = 0; $i < 100; $i++) {
            $number = $randomNumberGenerator->generate();

            $this->assertIsFloat($number);
            $this->assertGreaterThanOrEqual(0, $number);
            $this->assertLessThanOrEqual(1, $number);
        }
    }
}
