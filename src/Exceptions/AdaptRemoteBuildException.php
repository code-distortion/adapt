<?php

namespace CodeDistortion\Adapt\Exceptions;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Exceptions generated when building a database remotely.
 */
class AdaptRemoteBuildException extends AdaptException
{
    /** @var string|null The message to show in the log - so the regular exception message can be different. */
    private $messageForLog;

    /** @var string|null The url used to build a database remotely. */
    private $remoteBuildUrl;

    /** @var integer|null The response http status code. */
    private $responseStatusCode;

    /** @var string|null The error message generated by the remote server. */
    private $renderedResponseMessage;



    /**
     * The request to build a database remotely failed.
     *
     * @param string $driver The driver that isn't allowed to be built remotely.
     * @return self
     */
    public static function databaseTypeCannotBeBuiltRemotely($driver): self
    {
        return new self("$driver databases cannot be built remotely");
    }

    /**
     * The request to build a database remotely failed.
     *
     * @param string $remoteBuildUrl The "remote-build" url.
     * @return self
     */
    public static function remoteBuildUrlInvalid($remoteBuildUrl): self
    {
        return new self("The remote build url \"$remoteBuildUrl\" is invalid");
    }

    /**
     * The request to build a database remotely failed.
     *
     * @param string                 $connection        The connection the database was being built for.
     * @param string                 $remoteBuildUrl    The url used to build a database remotely.
     * @param ResponseInterface|null $response          The response to the build http request.
     * @param Throwable              $previousException The original exception.
     * @return self
     */
    public static function remoteBuildFailed(
        $connection,
        $remoteBuildUrl,
        $response,
        $previousException
    ): self {

        $renderedResponseMessage = self::buildResponseMessage(
            $remoteBuildUrl,
            $previousException,
            $response
        );

        $message = "The remote database for connection \"$connection\" could not be built - $renderedResponseMessage";

        $exception = new self($message, 0, $previousException);
        $exception->remoteBuildUrl = $remoteBuildUrl;
        $exception->responseStatusCode = $response ? $response->getStatusCode() : null;
        $exception->renderedResponseMessage = $renderedResponseMessage;
        $exception->messageForLog = "The remote database for connection \"$connection\" could not be built";

        return $exception;
    }





    /**
     * Get the http response status.
     *
     * @param string                 $remoteBuildUrl    The "remote-build" url.
     * @param Throwable|null         $previousException The original exception.
     * @param ResponseInterface|null $response          The response object returned by the remote Adapt installation.
     * @return string|null
     */
    private static function buildResponseMessage(
        string $remoteBuildUrl,
        $previousException,
        $response
    ) {

        $responseMessage = self::interpretRemoteMessage($response);

        if ($previousException instanceof ConnectException) {
            return "Could not connect to $remoteBuildUrl";
        } elseif ($previousException instanceof BadResponseException) {
            return $responseMessage
                ? "Remote error message: \"{$responseMessage}\""
                : null;
        } elseif (!is_null($responseMessage)) {
            return "Remote error message: \"{$responseMessage}\"";
        }
        return "Unknown error";
    }

    /**
     * Get the http response status.
     *
     * @param ResponseInterface|null $response The response object returned by the remote Adapt installation.
     * @return string|null
     */
    private static function interpretRemoteMessage($response)
    {
        if (!$response) {
            return null;
        }

        // don't bother with a message if it's a 404 - it's pretty self-explanatory
        if ($response->getStatusCode() == 404) {
            return null;
        }

        $responseMessage = $response->getBody()->getContents();
        return mb_strlen($responseMessage) > 200
            ? mb_substr($responseMessage, 0, 200) . '…'
            : $responseMessage;
    }



    /**
     * Generate the exception title to log.
     *
     * @return string
     */
    public function generateTitleForLog(): string
    {
        return 'The Remote Build Failed';
    }

    /**
     * Build the lines to log.
     *
     * @return string[]
     */
    public function generateLinesForLog(): array
    {
        // don't include the url if the connection couldn't be made
        // as the url is included in the message
        $e = $this->getPrevious();
        $url = (!$e instanceof ConnectException)
            ? $this->remoteBuildUrl . ($this->responseStatusCode ? " ($this->responseStatusCode)" : '')
            : null;

        if (!$this->messageForLog) {
            return array_filter([$this->getMessage()]);
        }

        return array_filter([
            $this->messageForLog,
            $url,
            $this->renderedResponseMessage,
        ]);
    }
}
