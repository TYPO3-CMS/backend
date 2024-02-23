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

namespace TYPO3\CMS\Backend\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormDataCompilerTest extends UnitTestCase
{
    protected FormDataCompiler $subject;
    protected FormDataGroupInterface&MockObject $formDataGroupMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formDataGroupMock = $this->createMock(FormDataGroupInterface::class);
        $this->subject = new FormDataCompiler();
    }

    #[Test]
    public function compileThrowsExceptionIfInputContainsKeysNotValidInResult(): void
    {
        $input = [
            'foo' => 'bar',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1440601540);
        $this->subject->compile($input, $this->formDataGroupMock);
    }

    #[Test]
    public function compileThrowsExceptionAtUnknownCommand(): void
    {
        $input = [
            'command' => 'unknownCommand',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437653136);
        $this->subject->compile($input, $this->formDataGroupMock);
    }

    #[Test]
    public function compileThrowsExceptionIfNoTableNameGiven(): void
    {
        $input = [
            'tableName' => '',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654409);
        $this->subject->compile($input, $this->formDataGroupMock);
    }

    #[Test]
    public function compileThrowsExceptionIfUidIsNotAnInteger(): void
    {
        $input = [
            'vanillaUid' => 'foo123',
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654247);
        $this->subject->compile($input, $this->formDataGroupMock);
    }

    #[Test]
    public function compileThrowsExceptionIfCommandIsEditAndUidIsNegative(): void
    {
        $input = [
            'command' => 'edit',
            'vanillaUid' => -100,
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437654332);
        $this->subject->compile($input, $this->formDataGroupMock);
    }

    #[Test]
    public function compileThrowsExceptionIfRequestIsNotProvidedInInitialDataArray(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1686867720);
        $this->expectExceptionMessage('The current ServerRequestInterface must be provided in key "request"');

        $this->subject->compile(
            [],
            $this->formDataGroupMock
        );
    }

    #[Test]
    public function compileReturnsResultArrayWithInputDataSet(): void
    {
        $input = [
            'request' => new ServerRequest(),
            'tableName' => 'pages',
            'vanillaUid' => 123,
            'command' => 'edit',
        ];
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturnArgument(0);
        $result = $this->subject->compile($input, $this->formDataGroupMock);
        self::assertEquals('pages', $result['tableName']);
        self::assertEquals(123, $result['vanillaUid']);
        self::assertEquals('edit', $result['command']);
    }

    #[Test]
    public function compileReturnsResultArrayWithAdditionalDataFormFormDataGroup(): void
    {
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturnCallback(static function (array $arguments): array {
            $result = $arguments;
            $result['databaseRow'] = 'newData';
            return $result;
        });
        $result = $this->subject->compile(
            [
                'request' => new ServerRequest(),
            ],
            $this->formDataGroupMock
        );
        self::assertEquals('newData', $result['databaseRow']);
    }

    #[Test]
    public function compileThrowsExceptionIfFormDataGroupDoesNotReturnArray(): void
    {
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturn(null);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1446664764);
        $this->subject->compile(
            [
                'request' => new ServerRequest(),
            ],
            $this->formDataGroupMock
        );
    }

    #[Test]
    public function compileThrowsExceptionIfRenderDataIsNotEmpty(): void
    {
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturn([
            'renderData' => [ 'foo' ],
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1485201279);
        $this->subject->compile(
            [
                'request' => new ServerRequest(),
            ],
            $this->formDataGroupMock
        );
    }

    #[Test]
    public function compileThrowsExceptionIfFormDataGroupRemovedKeysFromResultArray(): void
    {
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturnCallback(static function (array $arguments): array {
            $result = $arguments;
            unset($result['tableName']);
            return $result;
        });
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438079402);
        $this->subject->compile(
            [
                'request' => new ServerRequest(),
            ],
            $this->formDataGroupMock
        );
    }

    #[Test]
    public function compileThrowsExceptionIfFormDataGroupAddedKeysToResultArray(): void
    {
        $this->formDataGroupMock->method('compile')->with(self::anything())->willReturnCallback(static function (array $arguments): array {
            $result = $arguments;
            $result['newKey'] = 'newData';
            return $result;
        });
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438079402);
        $this->subject->compile(
            [
                'request' => new ServerRequest(),
            ],
            $this->formDataGroupMock
        );
    }
}
