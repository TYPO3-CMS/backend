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

namespace TYPO3\CMS\Backend\Tests\Unit\Wizard;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Wizard\WizardProviderFactory;
use TYPO3\CMS\Backend\Wizard\WizardProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WizardProviderFactoryTest extends UnitTestCase
{
    private WizardProviderFactory $subject;

    private MockObject|WizardProviderInterface $wizardProviderMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wizardProviderMock = $this->createConfiguredMock(WizardProviderInterface::class, [
            'getName' => 'foo',

        ]);
        $this->subject = new WizardProviderFactory(new \ArrayIterator([$this->wizardProviderMock]));
    }

    #[Test]
    public function returnsRequestsWizardProvider(): void
    {
        self::assertEquals($this->wizardProviderMock, $this->subject->getProvider('foo'));
    }

    #[Test]
    public function throwsExceptionOnMissingWizardProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->subject->getProvider('bar');
    }
}
