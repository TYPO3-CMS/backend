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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RouterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function routerReturnsRouteForAlias(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute(
            'new_route_identifier',
            new Route('/new/route/path', []),
            ['old_route_identifier']
        );
        self::assertTrue($subject->hasRoute('new_route_identifier'));
        self::assertTrue($subject->hasRoute('old_route_identifier'));
    }

    #[Test]
    public function matchResultFindsProperRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/login', $result->getRoute()->getPath());
    }

    #[Test]
    public function matchResultThrowsExceptionOnInvalidRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/this-path/does-not-exist', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(ResourceNotFoundException::class);
        $subject->matchResult($request);
    }

    #[Test]
    public function matchResultThrowsInvalidMethodForValidRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(MethodNotAllowedException::class);
        $subject->matchResult($request);
    }

    #[Test]
    public function matchResultReturnsRouteWithMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/login/password-reset/initiate-reset', $result->getRoute()->getPath());
    }

    #[Test]
    public function matchResultReturnsRouteForBackendModuleWithMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/module/site/configuration/delete', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/module/site/configuration/delete', $result->getRoute()->getPath());
        self::assertInstanceOf(ModuleInterface::class, $result->getRoute()->getOption('module'));
    }

    #[Test]
    public function matchResultThrowsExceptionForWrongHttpMethod(): void
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->expectExceptionCode(1612649842);

        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/module/site/configuration/delete', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject->matchResult($request);
    }

    #[Test]
    public function matchResultReturnsRouteWithPlaceholderAndMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute('custom-route', (new Route('/my-path/{identifier}', []))->setMethods(['POST']));
        $request = new ServerRequest('https://example.com/my-path/my-identifier', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('custom-route', $result->getRouteName());
        self::assertEquals(['identifier' => 'my-identifier'], $result->getArguments());
    }

    #[Test]
    public function matchResultReturnsRouteForSubRoute(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute('main_module', new Route('/module/main/module', []));
        $routeCollection = new RouteCollection();
        $routeCollection->add('subroute', new Route('/subroute', []));
        $routeCollection->addNamePrefix('main_module.');
        $routeCollection->addPrefix('/module/main/module');
        $subject->addRouteCollection($routeCollection);

        $resultMainModule = $subject->matchResult(new ServerRequest('/module/main/module'));
        self::assertEquals('main_module', $resultMainModule->getRouteName());
        self::assertEquals('/module/main/module', $resultMainModule->getRoute()->getPath());

        $resultSubRoute = $subject->matchResult(new ServerRequest('/module/main/module/subroute'));
        self::assertEquals('main_module.subroute', $resultSubRoute->getRouteName());
        self::assertEquals('/module/main/module/subroute', $resultSubRoute->getRoute()->getPath());
    }
}
