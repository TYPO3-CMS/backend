<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Tests\Unit\Wizard\DTO;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Wizard\DTO\Step;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StepTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('stepProvider')]
    public function stepCreationAndConfigurationDataBehaveAsExpected(
        string $module,
        array $configuration
    ): void {
        $step = Step::create($module);

        // Check initial state
        self::assertSame(
            ['module' => $module, 'configurationData' => []],
            $step->jsonSerialize()
        );

        $newStep = $step->withConfigurationData($configuration);

        // Original step remains unchanged
        /** @phpstan-ignore-next-line */
        self::assertSame([], $step->jsonSerialize()['configurationData']);

        // New step has configuration data
        self::assertSame($configuration, $newStep->jsonSerialize()['configurationData']);

        // Module remains the same
        self::assertSame($module, $newStep->jsonSerialize()['module']);

        // Ensure immutability
        self::assertNotSame($step, $newStep);
    }

    public static function stepProvider(): iterable
    {
        yield 'empty config' => [
            'my-module',
            [],
        ];

        yield 'single config key' => [
            'step-module-1',
            ['key1' => 'value1'],
        ];

        yield 'multiple config keys' => [
            'step-module-2',
            ['foo' => 'bar', 'baz' => 123, 'flag' => true],
        ];

        yield 'nested config array' => [
            'nested-module',
            ['level1' => ['level2' => ['key' => 'value']]],
        ];
    }
}
