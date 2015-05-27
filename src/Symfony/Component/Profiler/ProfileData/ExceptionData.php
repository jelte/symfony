<?php


namespace Symfony\Component\Profiler\ProfileData;


use Symfony\Component\Debug\Exception\FlattenException;

class ExceptionData implements ProfileDataInterface
{
    private $exception;

    public function __construct(FlattenException $exception = null)
    {
        $this->exception = $exception;
    }

    /**
     * Checks if the exception is not null.
     *
     * @return bool true if the exception is not null, false otherwise
     */
    public function hasException()
    {
        return isset($this->exception);
    }

    /**
     * Gets the exception.
     *
     * @return \Exception The exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Gets the exception message.
     *
     * @return string The exception message
     */
    public function getMessage()
    {
        return $this->exception->getMessage();
    }

    /**
     * Gets the exception code.
     *
     * @return int The exception code
     */
    public function getCode()
    {
        return $this->exception->getCode();
    }

    /**
     * Gets the status code.
     *
     * @return int The status code
     */
    public function getStatusCode()
    {
        return $this->exception->getStatusCode();
    }

    /**
     * Gets the exception trace.
     *
     * @return array The exception trace
     */
    public function getTrace()
    {
        return $this->exception->getTrace();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'exception';
    }
}