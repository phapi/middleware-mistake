<?php

namespace Phapi\Middleware\Mistake;

use Phapi\Contract\Di\Container;
use Phapi\Contract\Middleware\ErrorMiddleware;
use Phapi\Exception;
use Phapi\Exception\InternalServerError;
use Phapi\Http\Stream;
use Phapi\Http\Request;
use Phapi\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;

/**
 * Mistake Middleware
 *
 * The Mistake middleware handles errors and exceptions and makes
 * sure that the middleware pipeline is reset to only execute the
 * needed middleware for sending the response to the client.
 *
 * Before that is done the error and exception is logged using the
 * configured logger.
 *
 * @category Phapi
 * @package  Phapi\Middleware\Mistake
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/middleware-mistake
 */
class Mistake implements ErrorMiddleware
{

    /**
     * Dependency injection container
     *
     * @var Container
     */
    private $container;

    /**
     * List of fatal errors
     *
     * @var int
     */
    private $fatalErrors;

    /**
     * Should errors be displayed?
     *
     * @var bool
     */
    private $displayErrors = false;

    public function __construct($displayErrors = false)
    {
        $this->displayErrors = $displayErrors;

        // Register handlers
        $this->register();

        $this->fatalErrors = E_ERROR | E_USER_ERROR | E_COMPILE_ERROR | E_CORE_ERROR | E_PARSE;
    }

    /**
     * Register error, exception and shutdown handlers
     */
    private function register()
    {
        // Don't display any errors since we want this error handler to handle all
        // errors. Error messages sent to the client will be serialized.
        ini_set('display_errors', false);

        // In development however it is beneficiary to display errors.
        if ($this->displayErrors) {
            ini_set('display_errors', true);
        }

        // Register handlers
        register_shutdown_function([$this, 'shutdownHandler']);
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Catch errors in the shutdown process
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if ($error && $error['type'] & $this->fatalErrors) {
            $this->errorHandler(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

    /**
     * Set a custom error handler to make sure that errors are logged.
     * Allows any non-fatal errors to be logged.
     *
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @param array $errcontext
     * @throws \RuntimeException always when an error occurred to trigger the exception handler
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, array $errcontext = [])
    {
        $codes = array(
            256   => 'E_USER_ERROR',
            512   => 'E_USER_WARNING',
            1024  => 'E_USER_NOTICE',
            2048  => 'E_STRICT',
            4096  => 'E_RECOVERABLE_ERROR',
            8192  => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED',
            8     => 'E_NOTICE',
            2     => 'E_WARNING'
        );

        $message = 'Error of level ';
        if (array_key_exists($errno, $codes)) {
            $message .= $codes[$errno];
        } else {
            $message .= sprintf('Unknown error level, code of %d passed', $errno);
        }

        $message .= sprintf(
            '. Error message was "%s" in file %s at line %d.',
            $errstr,
            $errfile,
            $errline
        );

        // Log message
        $this->container['log']->error($message, $errcontext);
        $this->exceptionHandler(new InternalServerError('An unexpected error occurred.'));
    }

    /**
     * Handle thrown and uncaught exceptions thrown in the middleware
     * queue. The exception handler is registered when this Mistake
     * middleware is created. Any exceptions thrown before that won't
     * be caught by this handler.
     *
     * @param \Exception $exception
     */
    public function exceptionHandler(\Exception $exception)
    {
        // Add to log
        $this->logException($exception);

        // Try and get the latest request, or a new request
        $request =
            (isset($this->container['latestRequest']) ? $this->container['latestRequest'] :
                (isset($this->container['request']) ? $this->container['request'] : new Request())
            );

        // Try and get the latest response, or a new response
        $response =
            (isset($this->container['latestResponse']) ? $this->container['latestResponse'] :
                (isset($this->container['response']) ? $this->container['response'] : new Response())
            );

        // Check if exception is an instance of the Phapi Exception. If not, create
        // an InternalServerError Exception to get better error message to send to
        // the client.
        if (!($exception instanceof Exception)) {
            $exception = new InternalServerError(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        }

        // Prepare response with updated body (error message)
        // Reset body
        $response = $response->withBody(new Stream('php://memory', 'w+'));
        // Set status code
        $response = $response->withStatus($exception->getStatusCode());
        // Set error message
        $body = $this->prepareErrorBody($exception);

        // Set body to response
        $response = $response->withUnserializedBody($body);

        // Restart pipeline
        $this->container['pipeline']->prepareErrorQueue();
        $this->container['pipeline']($request, $response);
    }

    /**
     * Create a log entry about the error exception
     *
     * @param $exception
     */
    private function logException($exception)
    {
        // Prepare log message
        $message = sprintf(
            'Uncaught exception of type %s thrown in file %s at line %s%s.',
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage() ? sprintf(' with message "%s"', $exception->getMessage()) : ''
        );
        // Log error
        $this->container['log']->error($message, array(
            'Exception file'  => $exception->getFile(),
            'Exception line'  => $exception->getLine(),
            'Exception trace' => $exception->getTraceAsString()
        ));
    }

    /**
     * Takes an Error Exception and gets the available error information
     * and creates a body of it and returns the body.
     *
     * @param $exception
     * @return array
     */
    private function prepareErrorBody(Exception $exception)
    {
        // Prepare body
        $body = [ 'errors' => [] ];

        // Check if a message has been defined
        if (!empty($message = $exception->getMessage())) {
            $body['errors']['message'] = $message;
        }

        // Check if an error code has been defined
        if (!empty($code = $exception->getCode())) {
            $body['errors']['code'] = $code;
        }

        // Check if a description exists
        if (!empty($description = $exception->getDescription())) {
            $body['errors']['description'] = $description;
        }

        // Check if a link has been specified
        if (!empty($link = $exception->getLink())) {
            $body['errors']['link'] = $link;
        }

        return $body;
    }

    /**
     * Set dependency injection container
     *
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle the middleware pipeline call.
     *
     * Does not do much, just calls next middleware and returns
     * the response.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $response = $next($request, $response, $next);

        return $response;
    }
}