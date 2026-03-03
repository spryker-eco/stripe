<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Api;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use SprykerEco\Shared\Kernel\Transfer\TransferInterface;
use SprykerEco\Shared\Log\LoggerTrait;

class ApiLogger
{
    use LoggerTrait;

    /**
     * @param string $url
     * @param \SprykerEco\Shared\Kernel\Transfer\TransferInterface $requestTransfer
     *
     * @return void
     */
    public function logRequest(string $url, TransferInterface $requestTransfer): void
    {
        $this->getLogger()->info('Stripe request', [
            'url' => $url,
            'request' => $this->sanitizeLogData($requestTransfer->toArray()),
        ]);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $httpResponse
     * @param \SprykerEco\Shared\Kernel\Transfer\TransferInterface $responseTransfer
     *
     * @return void
     */
    public function logResponse(ResponseInterface $httpResponse, TransferInterface $responseTransfer): void
    {
        $this->getLogger()->info('Stripe response', [
            'status' => (string)$httpResponse->getStatusCode(),
            'response' => $this->sanitizeLogData($httpResponse->getBody()),
            'responseTransfer' => $this->sanitizeLogData($responseTransfer->toArray()),
        ]);
    }

    /**
     * @param \GuzzleHttp\Exception\RequestException $exception
     * @param \Psr\Http\Message\ResponseInterface|null $httpResponse
     *
     * @return void
     */
    public function logErrorRequest(RequestException $exception, ?ResponseInterface $httpResponse = null): void
    {
        $this->getLogger()->error('Stripe API request failed', [
            'exception' => $this->sanitizeLogData($exception),
            'status' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'body' => $httpResponse ? $this->sanitizeLogData($httpResponse->getBody()) : 'N/A',
        ]);
    }

    /**
     * TODO: sanitize the data that will be logged (remove/mask customer or other sensitive information)
     *
     * @param mixed $data
     *
     * @return mixed
     */
    protected function sanitizeLogData(mixed $data): mixed
    {
        return $data;
    }
}
