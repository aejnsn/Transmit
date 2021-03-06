<?php

namespace NavJobs\Transmit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Input;
use NavJobs\Transmit\Traits\QueryHelperTrait;
use Illuminate\Routing\Controller as BaseController;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

abstract class Controller extends BaseController
{
    use QueryHelperTrait;

    protected $statusCode = 200;
    protected $fractal;

    public function __construct()
    {
        $this->fractal = App::make(Fractal::class);

        $this->parseIncludes();
    }

    /**
     * Parses includes from either the header or query string.
     *
     * @return mixed
     */
    protected function parseIncludes()
    {
        if (Input::header('include')) {
            return $this->fractal->parseIncludes(Input::header('include'));
        }

        if (Input::get('include')) {
            return $this->fractal->parseIncludes(Input::get('include'));
        }

        return null;
    }

    /**
     * Returns the current status code.
     *
     * @return int
     */
    protected function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the current status code.
     *
     * @param $statusCode
     * @return $this
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Returns a json response that contains the specified resource
     * passed through fractal and optionally a transformer.
     *
     * @param $item
     * @param null $callback
     * @param null $resourceKey
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithItem($item, $callback = null, $resourceKey = null)
    {
        $rootScope = $this->fractal->item($item, $callback, $resourceKey);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Returns a json response that indicates the resource was successfully created also
     * returns the resource passed through fractal and optionally a transformer.
     *
     * @param $item
     * @param null $callback
     * @param null $resourceKey
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithItemCreated($item, $callback = null, $resourceKey = null)
    {
        $this->setStatusCode(201);
        $rootScope = $this->fractal->item($item, $callback, $resourceKey);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Returns a json response that contains the specified collection
     * passed through fractal and optionally a transformer.
     *
     * @param $collection
     * @param $callback
     * @param null $resourceKey
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithCollection($collection, $callback, $resourceKey = null)
    {
        $rootScope = $this->fractal->collection($collection, $callback, $resourceKey);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Returns a json response that contains the specified paginated collection
     * passed through fractal and optionally a transformer.
     *
     * @param $builder
     * @param $callback
     * @param int $perPage
     * @param null $resourceKey
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithPaginatedCollection($builder, $callback, $perPage = 10, $resourceKey = null)
    {
        $paginator = $builder->paginate($perPage);
        $paginator->appends($this->getQueryParameters());

        $rootScope = $this->fractal
            ->collection($paginator->getCollection(), $callback, $resourceKey)
            ->paginateWith(new IlluminatePaginatorAdapter($paginator));

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Returns an array of Query Parameters, not including pagination.
     *
     * @return array
     */
    protected function getQueryParameters()
    {
        return array_diff_key($_GET, array_flip(['page']));
    }

    /**
     * Returns a json response that contains the specified array,
     * the current status code and optional headers.
     *
     * @param array $array
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithArray(array $array, array $headers = [])
    {
        return response()->json($array, $this->statusCode, $headers);
    }

    /**
     * Returns a response that indicates success but no content returned.
     *
     * @return \Illuminate\Http\Response
     */
    protected function respondWithNoContent()
    {
        return response()->make('', 204);
    }

    /**
     * Returns a response that indicates a 403 Forbidden.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Returns a response that indicates an Internal Error has occurred.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Returns a response that indicates a 404 Not Found.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Returns a response that indicates a 401 Unauthorized.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Returns a response that indicates a 422 Unprocessable Entity.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorUnprocessableEntity($message = 'Unprocessable Entity')
    {
        return $this->setStatusCode(422)->respondWithError($message);
    }

    /**
     * Returns a response that indicates the wrong arguments were specified.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)->respondWithError($message);
    }

    /**
     * Returns a response that indicates custom error type.
     *
     * @param $message
     * @param $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorCustomType($message, $statusCode = 400)
    {
        return $this->setStatusCode($statusCode)->respondWithError($message);
    }

    /**
     * Returns a response that indicates multiple errors in an array.
     *
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorArray(array $errors)
    {
        return $this->setStatusCode(422)->respondWithArray(['errors' => $errors]);
    }

    /**
     * Returns a response that indicates an an error occurred.
     *
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    private function respondWithError($message)
    {
        return $this->respondWithArray([
            'errors' => [
                'http_code' => $this->statusCode,
                'message'   => $message,
            ]
        ]);
    }
}
