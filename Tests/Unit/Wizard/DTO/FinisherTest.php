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
use TYPO3\CMS\Backend\Wizard\DTO\Finisher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FinisherTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('finisherProvider')]
    public function finisherFactoriesReturnExpectedStructure(
        Finisher $finisher,
        array $expected
    ): void {
        self::assertSame($expected, $finisher->jsonSerialize());
    }

    public static function finisherProvider(): iterable
    {
        yield 'redirect finisher' => [
            Finisher::createRedirectFinisher(
                'https://example.com',
                'Success',
                'Redirecting...'
            ),
            [
                'identifier' => 'redirect',
                'module' => '@typo3/backend/wizard/finisher/redirect-finisher.js',
                'data' => ['url' => 'https://example.com'],
                'labels' => [
                    'successTitle' => 'Success',
                    'successDescription' => 'Redirecting...',
                ],
            ],
        ];

        yield 'noop finisher' => [
            Finisher::createNoopFinisher(
                'Done',
                'Nothing to do'
            ),
            [
                'identifier' => 'noop',
                'module' => '@typo3/backend/wizard/finisher/noop-finisher.js',
                'data' => [],
                'labels' => [
                    'successTitle' => 'Done',
                    'successDescription' => 'Nothing to do',
                ],
            ],
        ];

        yield 'reload finisher' => [
            Finisher::createReloadFinisher(
                'Reloaded',
                'Page will reload'
            ),
            [
                'identifier' => 'reload',
                'module' => '@typo3/backend/wizard/finisher/reload-finisher.js',
                'data' => [],
                'labels' => [
                    'successTitle' => 'Reloaded',
                    'successDescription' => 'Page will reload',
                ],
            ],
        ];

        yield 'custom finisher' => [
            Finisher::createCustomFinisher(
                'custom',
                'my/module.js',
                'Custom',
                'Custom message',
                ['foo' => 'bar']
            ),
            [
                'identifier' => 'custom',
                'module' => 'my/module.js',
                'data' => ['foo' => 'bar'],
                'labels' => [
                    'successTitle' => 'Custom',
                    'successDescription' => 'Custom message',
                ],
            ],
        ];
    }
}
