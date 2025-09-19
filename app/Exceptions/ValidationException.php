<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $statusCode = 422;
    protected $errorType = 'validation_error';
    protected $errors = [];
    
    /**
     * Create a new validation exception.
     *
     * @param string $message
     * @param array $errors
     * @param int $statusCode
     */
    public function __construct($message = 'Validation failed', $errors = [], $statusCode = 422)
    {
        parent::__construct($message);
        $this->errors = $errors;
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
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
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
                'message' => $this->getMessage(),
                'error_type' => $this->errorType,
                'errors' => $this->errors,
                'status_code' => $this->statusCode
            ], $this->statusCode);
        }
        
        return response()->view('errors.validation', [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'statusCode' => $this->statusCode
        ], $this->statusCode);
    }
}