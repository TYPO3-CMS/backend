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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\Wizard;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Wizard\PageWizardController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageWizardControllerTest extends UnitTestCase
{
    private PageWizardController $subject;

    private MockObject|PageRepository $pageRepositoryMock;

    private MockObject|IconFactory $iconFactoryMock;

    private MockObject|FormDataCompiler $formDataCompilerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pageRepositoryMock = $this->createMock(PageRepository::class);
        $this->iconFactoryMock = $this->createMock(IconFactory::class);
        $this->formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        $this->subject = new PageWizardController(
            $this->pageRepositoryMock,
            $this->iconFactoryMock,
            $this->formDataCompilerMock,
        );
    }

    #[Test]
    public function allDoktypesAreReturnedFromFormDataCompiler(): void
    {
        $selectItem = new SelectItem(
            type: 'select',
            label: 'Label',
            value: 123,
            icon: 'icon-page',
            description: 'Test description'
        );

        $formDataCompilerResult = [];
        $formDataCompilerResult['processedTca']['columns']['doktype']['config']['items'] = [$selectItem->toArray()];

        $this->formDataCompilerMock
            ->expects($this->once())
            ->method('compile')
            ->willReturn($formDataCompilerResult);

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getQueryParams')
            ->willReturn([]);

        $response = $this->subject->getDoktypesAction($requestMock);

        self::assertInstanceOf(JsonResponse::class, $response);
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            [
                'value' => 123,
                'label' => 'Label',
                'icon' => 'icon-page',
                'description' => 'Test description',
            ],
        ], $payload);
    }

    #[Test]
    public function detectsCorrectParentOnAfterPositionInGetDoktypesAction(): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getQueryParams')->willReturn([
            'data' => [
                'position' => [
                    'pageUid' => 2,
                    'insertPosition' => 'after',
                ],
            ],
        ]);

        $this->pageRepositoryMock->expects($this->once())
            ->method('getPage')
            ->with(2)
            ->willReturn(['pid' => 99]);

        $this->subject->getDoktypesAction($requestMock);
    }

    #[Test]
    public function getPageDetailActionReturns400IfPageUidIsMissing(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            ['error' => 'Missing required query parameter: pageUid'],
            $body
        );
    }

    #[Test]
    public function getPageDetailActionReturns404IfPageDoesNotExist(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageUid' => '999',
        ]);

        $this->pageRepositoryMock->expects($this->once())
            ->method('getPage')
            ->with(999)
            ->willReturn([]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            ['error' => 'Page not found for pageUid: 999'],
            $body
        );
    }

    #[Test]
    public function getPageDetailActionReturnsPageDetailsIfPageExists(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageUid' => '999',
        ]);

        $this->pageRepositoryMock->expects($this->once())
            ->method('getPage')
            ->with(999)
            ->willReturn([
                'uid' => 999,
                'title' => 'Test Page',
            ]);

        $this->iconFactoryMock->expects($this->once())
            ->method('getIconForRecord')
            ->with(
                'pages',
                [
                    'uid' => 999,
                    'title' => 'Test Page',
                ],
                IconSize::SMALL
            )
            ->willReturn($this->createConfiguredMock(Icon::class, ['getIdentifier' => 'apps-pagetree-page-default']))
        ;

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        self::assertSame([
            'uid' => 999,
            'title' => 'Test Page',
            'icon' => 'apps-pagetree-page-default',
        ], $body);
    }

    #[Test]
    public function getPageDetailActionReturnsStaticRootOnPageUidZero(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageUid' => '0',
        ]);

        $response = $this->subject->getPageDetailAction($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);

        self::assertSame([
            'uid' => 0,
            'title' => 'TYPO3',
            'icon' => 'apps-pagetree-root',
        ], $body);
    }
}
