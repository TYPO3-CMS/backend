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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Wizard\DTO\Configuration;
use TYPO3\CMS\Backend\Wizard\DTO\Step;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConfigurationTest extends UnitTestCase
{
    #[Test]
    public function createReturnsConfigurationWithSteps(): void
    {
        $step1 = Step::create('module-1');
        $step2 = Step::create('module-2');

        $config = Configuration::create([$step1, $step2]);

        self::assertSame([$step1, $step2], $config->getSteps());

        $expectedJson = [
            'steps' => [
                ['module' => 'module-1', 'configurationData' => []],
                ['module' => 'module-2', 'configurationData' => []],
            ],
        ];

        self::assertSame($expectedJson, $config->jsonSerialize());
    }

    #[Test]
    public function constructorThrowsExceptionForNonStepElements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/All elements of \$steps must be instances of WizardStepDTO/');

        // @phpstan-ignore-next-line
        Configuration::create([new \stdClass()]);
    }

    #[Test]
    public function createWithEmptyArrayReturnsEmptySteps(): void
    {
        $config = Configuration::create([]);

        self::assertSame([], $config->getSteps());
        self::assertSame(['steps' => []], $config->jsonSerialize());
    }
}
