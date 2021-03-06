<?php

namespace NavJobs\Transmit\Test\Integration;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use ReflectionClass;
use ReflectionMethod;

class ControllerTest extends TestCase
{

    protected $controller;

    public function setUp()
    {
        parent::setUp();

        $this->controller = new ReflectionClass(TestController::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_header_includes()
    {
        request()->headers->add(['include' => 'test']);

        $controller = new TestController();

        $this->assertTrue(isset($controller));
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_query_string_includes()
    {
        request()->query->add(['include' => 'test']);

        $controller = new TestController();

        $this->assertTrue(isset($controller));
    }

    /**
     * @test
     */
    public function it_gets_status_codes()
    {
        $method = new ReflectionMethod(
            TestController::class, 'getStatusCode'
        );

        $method->setAccessible(TRUE);

        $this->assertEquals(
            200, $method->invoke(new TestController())
        );
    }

    /**
     * @test
     */
    public function it_can_set_status_codes()
    {
        $setStatusCode = $this->getMethod('setStatusCode');
        $getStatusCode = $this->getMethod('getStatusCode');

        $testController = new TestController();
        $setStatusCode->invokeArgs($testController, [400]);

        $this->assertEquals(
            400, $getStatusCode->invoke($testController)
        );
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_single_item()
    {
        $respondWithItem = $this->getMethod('respondWithItem');
        $testController = new TestController();

        $response = $respondWithItem->invokeArgs($testController, [$this->testBooks[0], new TestTransformer()]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = ['data' => [
            'id' => 1, 'author' => 'Philip K Dick', ]];

        $this->assertEquals($expectedArray, $array);
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_single_item_at_the_top_level()
    {
        $respondWithItem = $this->getMethod('respondWithItem');
        $testController = new TestController();

        $response = $respondWithItem->invokeArgs($testController, [$this->testBooks[0], new TestTransformer(), false]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'id' => 1, 'author' => 'Philip K Dick', ];

        $this->assertEquals($expectedArray, $array);
    }

    /**
     * @test
     */
    public function it_can_respond_with_item_created()
    {
        $respondWithItemCreated = $this->getMethod('respondWithItemCreated');
        $getStatusCode = $this->getMethod('getStatusCode');
        $testController = new TestController();

        $response = $respondWithItemCreated->invokeArgs($testController, [$this->testBooks[0], new TestTransformer()]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = ['data' => [
            'id' => 1, 'author' => 'Philip K Dick', ]];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals(
            201, $getStatusCode->invoke($testController)
        );
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_collection()
    {
        $respondWithCollection = $this->getMethod('respondWithCollection');
        $testController = new TestController();

        $expectedData = [
            ['id' => 1, 'author' => 'Philip K Dick'],
            ['id' => 2, 'author' => 'George R. R. Satan'],
        ];

        $response = $respondWithCollection->invokeArgs($testController, [$this->testBooks, new TestTransformer()]);
        $array = json_decode(json_encode($response->getData()), true);

        $this->assertEquals(['data' => $expectedData], $array);
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_collection_as_a_top_level_item()
    {
        $respondWithCollection = $this->getMethod('respondWithCollection');
        $testController = new TestController();

        $expectedData = [
            ['id' => 1, 'author' => 'Philip K Dick'],
            ['id' => 2, 'author' => 'George R. R. Satan'],
        ];

        $response = $respondWithCollection->invokeArgs($testController, [
            $this->testBooks,
            new TestTransformer(),
            false
        ]);
        $array = json_decode(json_encode($response->getData()), true);

        $this->assertEquals($expectedData, $array);
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_paginated_collection()
    {
        $respondWithPaginatedCollection = $this->getMethod('respondWithPaginatedCollection');
        $testController = new TestController();
        $lengthAwarePaginator = new LengthAwarePaginator($this->testBooks, count($this->testBooks), 10);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('paginate')->once()->andReturn($lengthAwarePaginator);

        $response = $respondWithPaginatedCollection->invokeArgs($testController, [$builder, new TestTransformer(), 10]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'data' => [
                ['id' => 1, 'author' => 'Philip K Dick'],
                ['id' => 2, 'author' => 'George R. R. Satan'],
            ],
            'pagination' => [
                'total' => 2,
                'count' => 2,
                'per_page' => 10,
                'current_page' => 1,
                'total_pages' => 1,
                'links' => []
            ]
        ];

        $this->assertEquals($expectedArray, $array);
    }

    /**
     * @test
     */
    public function it_can_get_query_parameters()
    {
        $getQueryParameters = $this->getMethod('getQueryParameters');
        $testController = new TestController();

        $_GET = ['page' => 1, 'include' => 'test'];

        $parameters = $getQueryParameters->invoke($testController);

        $this->assertEquals(['include' => 'test'], $parameters);
    }

    /**
     * @test
     */
    public function it_can_respond_with_an_array()
    {
        $respondWithArray = $this->getMethod('respondWithArray');
        $testController = new TestController();

        $response = $respondWithArray->invokeArgs($testController, [$this->testBooks[0]]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'id' => 1,
            'title' => 'Hogfather',
            'yr' => '1998',
            'author_name' => 'Philip K Dick',
            'author_email' => 'philip@example.org',
            'characters' => [
                [
                    'name' => 'Death'
                ],
                [
                    'name' => 'Hex'
                ]
            ],
            'publisher' => 'Elephant books'
        ];

        $this->assertEquals($expectedArray, $array);
    }

    /**
     * @test
     */
    public function it_can_respond_with_no_content()
    {
        $respondWithNoContent = $this->getMethod('respondWithNoContent');
        $testController = new TestController();

        $response = $respondWithNoContent->invoke($testController);

        $this->assertEquals('204', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_error_forbidden()
    {
        $errorForbidden = $this->getMethod('errorForbidden');
        $testController = new TestController();

        $response = $errorForbidden->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 403,
                'message' => 'Forbidden'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('403', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_an_internal_error()
    {
        $errorInternalError = $this->getMethod('errorInternalError');
        $testController = new TestController();

        $response = $errorInternalError->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 500,
                'message' => 'Internal Error'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('500', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_not_found_error()
    {
        $errorNotFound = $this->getMethod('errorNotFound');
        $testController = new TestController();

        $response = $errorNotFound->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 404,
                'message' => 'Resource Not Found'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('404', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_unauthorized_error()
    {
        $errorUnauthorized = $this->getMethod('errorUnauthorized');
        $testController = new TestController();

        $response = $errorUnauthorized->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 401,
                'message' => 'Unauthorized'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('401', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_an_unprocessable_entity()
    {
        $errorUnprocessableEntity = $this->getMethod('errorUnprocessableEntity');
        $testController = new TestController();

        $response = $errorUnprocessableEntity->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 422,
                'message' => 'Unprocessable Entity'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('422', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_wrong_arguments_error()
    {
        $errorWrongArgs = $this->getMethod('errorWrongArgs');
        $testController = new TestController();

        $response = $errorWrongArgs->invoke($testController);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 400,
                'message' => 'Wrong Arguments'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('400', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_a_custom_error()
    {
        $errorCustomType = $this->getMethod('errorCustomType');
        $testController = new TestController();

        $response = $errorCustomType->invokeArgs($testController, ['Test error', 402]);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'http_code' => 402,
                'message' => 'Test error'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('402', $response->status());
    }

    /**
     * @test
     */
    public function it_can_respond_with_an_array_of_errors()
    {
        $errorArray = $this->getMethod('errorArray');
        $testController = new TestController();

        $response = $errorArray->invoke($testController, ['field_name' => 'This field_name had an error.']);
        $array = json_decode(json_encode($response->getData()), true);

        $expectedArray = [
            'errors' => [
                'field_name' => 'This field_name had an error.'
            ]
        ];

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals('422', $response->status());
    }

    /**
     * @param $methodName
     * @return mixed
     */
    protected function getMethod($methodName)
    {
        $method = $this->controller->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
