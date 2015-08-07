<?php namespace Stevenmaguire\Laravel\Http\Middleware\Test;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Mockery as m;
use Stevenmaguire\Laravel\Http\Middleware\EnforceContentSecurity;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class EnforceContentSecurityTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->middleware = new EnforceContentSecurity;
        $this->configShouldReturn(null);
    }

    protected function getTestConfig()
    {
        return [
            'default' => 'test',
            'profiles' => [
                'test' => [
                    'base-uri' => [
                        'http://domain.com',
                        'http://domain.co',
                        'http://domain.biz',
                    ],
                ],
                'test2' => [
                    'base-uri' => [
                        'http://domain.online',
                        'http://domain.music',
                        'http://domain.chickens',
                    ],
                ]
            ]
        ];
    }

    protected function configShouldReturn($value)
    {
        $this->middleware->setConfigClosure(function ($key) use ($value) {
            return $value;
        });
    }

    public function testResponseUnaffectedWhenJsonResponse()
    {
        $request = m::mock(Request::class);
        $response = m::mock(JsonResponse::class);
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($response, $result);
    }

    public function testResponseUnaffectedWhenRedirectResponse()
    {
        $request = m::mock(Request::class);
        $response = m::mock(RedirectResponse::class);
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals($response, $result);
    }

    public function testProfileConfigAlwaysReturnsAnArray()
    {
        $statusCode = 200;
        $body = uniqid();
        $request = m::mock(Request::class);
        $headers = m::mock(ResponseHeaderBag::class);
        $headers->shouldReceive('all')->andReturn([]);
        $response = m::mock(Response::class);
        $response->headers = $headers;
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getContent')->andReturn($body);
        $response->shouldReceive('getProtocolVersion')->andReturn('1.1');
        $next = function ($request) use ($response) {
            return $response;
        };

        $this->configShouldReturn(null);

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($statusCode, $response->status());
        $this->assertEquals($body, $response->getOriginalContent());
        $this->assertNull($response->headers->get('content-security-policy'));
    }

    public function testContentSecurityAdded()
    {
        $statusCode = 200;
        $body = uniqid();
        $expectedPolicy = 'base-uri http://domain.biz http://domain.co http://domain.com';
        $request = m::mock(Request::class);
        $headers = m::mock(ResponseHeaderBag::class);
        $headers->shouldReceive('all')->andReturn([]);
        $response = m::mock(Response::class);
        $response->headers = $headers;
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getContent')->andReturn($body);
        $response->shouldReceive('getProtocolVersion')->andReturn('1.1');
        $next = function ($request) use ($response) {
            return $response;
        };

        $this->configShouldReturn($this->getTestConfig());

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($statusCode, $response->status());
        $this->assertEquals($body, $response->getOriginalContent());
        $this->assertEquals($expectedPolicy, $response->headers->get('content-security-policy'));
    }

    public function testContentSecurityAddedWithGivenProfile()
    {
        $statusCode = 200;
        $body = uniqid();
        $expectedPolicy = 'base-uri http://domain.biz http://domain.chickens http://domain.co http://domain.com http://domain.music http://domain.online';
        $request = m::mock(Request::class);
        $headers = m::mock(ResponseHeaderBag::class);
        $headers->shouldReceive('all')->andReturn([]);
        $response = m::mock(Response::class);
        $response->headers = $headers;
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getContent')->andReturn($body);
        $response->shouldReceive('getProtocolVersion')->andReturn('1.1');
        $next = function ($request) use ($response) {
            return $response;
        };

        $this->configShouldReturn($this->getTestConfig());

        $response = $this->middleware->handle($request, $next, 'test2');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($statusCode, $response->status());
        $this->assertEquals($body, $response->getOriginalContent());
        $this->assertEquals($expectedPolicy, $response->headers->get('content-security-policy'));
    }

    public function testContentSecurityAddedWithOnlyGivenProfiles()
    {
        $statusCode = 200;
        $body = uniqid();
        $expectedPolicy = 'base-uri http://domain.chickens http://domain.music http://domain.online';
        $request = m::mock(Request::class);
        $headers = m::mock(ResponseHeaderBag::class);
        $headers->shouldReceive('all')->andReturn([]);
        $response = m::mock(Response::class);
        $response->headers = $headers;
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getContent')->andReturn($body);
        $response->shouldReceive('getProtocolVersion')->andReturn('1.1');
        $next = function ($request) use ($response) {
            return $response;
        };
        $config = $this->getTestConfig();
        unset($config['default']);

        $this->configShouldReturn($config);

        $response = $this->middleware->handle($request, $next, 'test2');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($statusCode, $response->status());
        $this->assertEquals($body, $response->getOriginalContent());
        $this->assertEquals($expectedPolicy, $response->headers->get('content-security-policy'));
    }
}
