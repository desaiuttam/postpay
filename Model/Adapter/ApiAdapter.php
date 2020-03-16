<?php
/**
 * Copyright © Postpay. All rights reserved.
 * See LICENSE for license details.
 */
namespace Postpay\Payment\Model\Adapter;

use Magento\Payment\Model\Method\Logger;
use Postpay\Exceptions\ApiException;
use Postpay\Payment\Gateway\Config\Config;
use Postpay\PostpayFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ApiAdapter
 */
class ApiAdapter
{
    const ISO_DATE_FORMAT = 'Y-m-d';
    const ISO_DATETIME_FORMAT = \DateTime::ISO8601;

    /**
     * @var \Postpay\Payment
     */
    protected $client;

    /**
     * @var PostpayFactory
     */
    protected $postpayFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * Constructor.
     *
     * @param PostpayFactory $postpayFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     */
    public function __construct(
        PostpayFactory $postpayFactory,
        Config $config,
        LoggerInterface $logger,
        Logger $customLogger
    ) {
        $this->postpayFactory = $postpayFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->customLogger = $customLogger;
        $this->client = $this->postpayFactory->create([
            'config' => [
                'merchant_id' => $this->config->getMerchantId(),
                'secret_key' => $this->config->getSecretKey(),
                'sandbox' => $this->config->isSandbox(),
                'client_handler' => 'guzzle'
            ]
        ]);
    }

    /**
     * Send a request to API and returns the response.
     *
     * @param string $method
     * @param string $path
     * @param array $params
     *
     * @return array
     *
     * @throws ApiException
     */
    public function request($method, $path, array $params = [])
    {
        try {
            $response = $this->client->request($method, $path, $params)->json();
        } catch (ApiException $e) {
            $this->logger->critical($e->getMessage());
            $response = [];
            throw $e;
        } finally {
            $this->customLogger->debug([
                'path' => $path,
                'request' => $params,
                'response' => $response
            ]);
        }
        return $response;
    }

    /**
     * Convert float to decimal.
     *
     * @param float $value
     *
     * @return int
     */
    public static function decimal($value)
    {
        return (int) round($value * 100);
    }

    /**
     * Convert date to ApiAdapter::ISO_DATE_FORMAT.
     *
     * @param string $value
     *
     * @return string
     */
    public static function date($value)
    {
        return (new \DateTime($value))->format(self::ISO_DATE_FORMAT);
    }

    /**
     * Convert date to ApiAdapter::ISO_DATETIME_FORMAT.
     *
     * @param string $value
     *
     * @return string
     */
    public static function datetime($value)
    {
        return (new \DateTime($value))->format(self::ISO_DATETIME_FORMAT);
    }
}