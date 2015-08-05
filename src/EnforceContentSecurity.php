<?php namespace Stevenmaguire\Http\Middleware\Laravel;

use Closure;
use Illuminate\Http\Response;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;
use Stevenmaguire\Http\Middleware\EnforceContentSecurity as BaseMiddleware;

class EnforceContentSecurity extends BaseMiddleware
{
    /**
     * Config closure;
     *
     * @var Closure
     */
    protected $config;

    /**
     * Creates new middleware instance.
     */
    public function __construct()
    {
        $this->setConfigClosure(function ($key = null, $default = null) {
            // @codeCoverageIgnoreStart
            if (function_exists('config')) {
                return config($key, $default);
            }

            return null;
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * Handles an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $this->setProfiles($this->getProfileConfig());

            $this->setProfilesWithParameters(func_get_args());

            $psr7Response = $this->createPsr7Response($response);

            $psr7Response = $this->addPolicyHeader($psr7Response);

            $response = $this->createLaravelResponse($psr7Response);
        }

        return $response;
    }

    /**
     * Creates Laravel response object from PSR 7 response.
     *
     * @param  ResponseInterface  $response
     *
     * @return Response
     */
    protected function createLaravelResponse(ResponseInterface $response)
    {
        return new Response(
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }

    /**
     * Creates PSR 7 response object from Laravel response.
     *
     * @param  Response  $response
     *
     * @return ResponseInterface
     */
    protected function createPsr7Response(Response $response)
    {
        return new PsrResponse(
            $response->getStatusCode(),
            $response->headers->all(),
            $response->getContent(),
            $response->getProtocolVersion()
        );
    }

    /**
     * Retrives profile configuration from Laravel config object.
     *
     * @return array
     */
    protected function getProfileConfig()
    {
        $configCallable = $this->config;
        $config = $configCallable($this->getProfileConfigKey());

        if (!is_array($config)) {
            $config = [$config];
        }

        return array_filter($config);
    }

    /**
     * Retrieves configuration key associated with content security profiles.
     *
     * @return string
     */
    protected function getProfileConfigKey()
    {
        return 'security.content';
    }

    /**
     * Gets profiles from handle method arguments.
     *
     * @param  array $arguments
     *
     * @return array
     */
    protected function getProfilesFromArguments(array $arguments)
    {
        $profiles = [];
        if (count($arguments) > 2) {
            unset($arguments[0]);
            unset($arguments[1]);
            $profiles = $arguments;
        }
        return $profiles;
    }

    /**
     * Updates config callable used to access application configuration data.
     *
     * @param Closure  $config
     *
     * @return EnforceContentSecurity
     */
    public function setConfigClosure(Closure $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Updates policy configuration with rules from each profile in given parameters.
     *
     * @param array  $parameters
     *
     * @return void
     */
    protected function setProfilesWithParameters(array $parameters)
    {
        $profiles = $this->getProfilesFromArguments($parameters);
        array_map([$this, 'loadProfileByKey'], $profiles);
    }
}
