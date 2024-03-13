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

namespace TYPO3\CMS\Backend\Tests\Functional\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutViewTest extends FunctionalTestCase
{
    private const RUNTIME_CACHE_ENTRY = 'backendUtilityBeGetRootLine';

    private FrontendInterface $runtimeCache;
    private BackendLayoutView&MockObject&AccessibleObjectInterface $backendLayoutView;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runtimeCache = $this->get(CacheManager::class)->getCache('runtime');
        $this->backendLayoutView = $this->getAccessibleMock(
            BackendLayoutView::class,
            ['getPage'],
            [],
            '',
            false
        );
    }

    protected function tearDown(): void
    {
        $this->runtimeCache->remove(self::RUNTIME_CACHE_ENTRY);
        parent::tearDown();
    }

    #[DataProvider('selectedCombinedIdentifierIsDeterminedDataProvider')]
    #[Test]
    public function selectedCombinedIdentifierIsDetermined(false|string $expected, array $page, array $rootLine): void
    {
        $pageId = $page['uid'];
        if ($pageId !== false) {
            $this->mockRootLine((int)$pageId, $rootLine);
        }

        $this->backendLayoutView->expects(self::once())
            ->method('getPage')->with(self::equalTo($pageId))
            ->willReturn($page);

        $selectedCombinedIdentifier = $this->backendLayoutView->_call('getSelectedCombinedIdentifier', $pageId);
        self::assertEquals($expected, $selectedCombinedIdentifier);
    }

    public static function selectedCombinedIdentifierIsDeterminedDataProvider(): array
    {
        return [
            'first level w/o layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with layout' => [
                '1',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with provided layout' => [
                'mine_current',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with provided next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level w/o layout, first level with layout' => [
                '0',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level w/o layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with layout, first level with next layout' => [
                '2',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with layouts, first level resetting all layouts' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with provided layouts, first level resetting all layouts' => [
                'mine_current',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level resetting layout, first level with next layout' => [
                false,
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level resetting next layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level w/o layout, second level resetting layout, first level with next layout' => [
                '1',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level w/o layout, second level resetting next layout, first level with next layout' => [
                false,
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level with provided layouts, second level w/o layout, first level resetting layouts' => [
                'mine_current',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
        ];
    }

    private function mockRootLine(int $pageId, array $rootLine): void
    {
        $this->runtimeCache->set(self::RUNTIME_CACHE_ENTRY, [
            $pageId . '--' => $rootLine, // plain, no overlay
            $pageId . '--1' => $rootLine, // workspace overlay
        ]);
    }
}
