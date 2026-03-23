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
use TYPO3\CMS\Backend\Wizard\DTO\SubmissionResult;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SubmissionResultTest extends UnitTestCase
{
    #[Test]
    public function createSuccessResultReturnsExpectedState(): void
    {
        $finisher = Finisher::createNoopFinisher('Done', 'Everything fine');
        $result = SubmissionResult::createSuccessResult($finisher);

        self::assertTrue($result->isSuccess());
        self::assertFalse($result->hasErrors());

        self::assertSame(
            [
                'success' => true,
                'finisher' => $finisher->jsonSerialize(),
            ],
            $result->jsonSerialize()
        );
    }

    #[Test]
    public function createErrorResultReturnsExpectedState(): void
    {
        $errors = ['Something went wrong'];
        $result = SubmissionResult::createErrorResult($errors);

        self::assertFalse($result->isSuccess());
        self::assertTrue($result->hasErrors());

        self::assertSame(
            [
                'success' => false,
                'errors' => $errors,
            ],
            $result->jsonSerialize()
        );
    }

    #[Test]
    #[DataProvider('errorProvider')]
    public function hasErrorsReflectsErrorState(array $errors, bool $expected): void
    {
        $result = SubmissionResult::createErrorResult($errors);

        self::assertSame($expected, $result->hasErrors());
    }

    public static function errorProvider(): iterable
    {
        yield 'no errors' => [
            [],
            false,
        ];

        yield 'single error' => [
            ['Error message'],
            true,
        ];

        yield 'multiple errors' => [
            ['Error 1', 'Error 2'],
            true,
        ];
    }
}
