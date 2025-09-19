<?php

namespace App\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    protected $statusCode = 401;
    protected $errorType = 'authentication_error';
    
    /**
     * Create a new authentication exception.
     *
     * @param string $message
     * @param int $statusCode
     */
    public function __construct($message = 'Authentication failed', $statusCode = 401)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }
    
    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Get the error type.
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }
    
    /**
     * Render the exception as an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => $this->getMessage(),
                'error_type' => $this->errorType,
                'status_code' => $this->statusCode
            ], $this->statusCode);
        }
        
        return response()->view('errors.authentication', [
            'message' => $this->getMessage(),
            'statusCode' => $this->statusCode
        ], $this->statusCode);
    }
}