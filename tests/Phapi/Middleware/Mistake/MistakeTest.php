<?php

namespace Phapi\Tests\Middleware\Mistake\Mistake;

use Phapi\Http\Request;
use Phapi\Http\Response;
use Phapi\Middleware\Mistake\Mistake;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Middleware\Mistake\Mistake
 */
class MistakeTest extends TestCase
{

    public function testConstructor()
    {
        $mistake = new Mistake($displayErrors = true);
        $container = \Mockery::mock('Phapi\Contract\Di\Container');

        $mistake->setContainer($container);

        $request = new Request();
        $response = new Response();

        $return = $mistake($request, $response, function($request, $response) { return $response; });

        $this->assertSame($response, $return);
    }
}
