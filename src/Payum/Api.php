<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum;

use InvalidArgumentException;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;

final class Api
{
    public const STATUS_NEW = 'new';
    public const STATUS_SUCCESS = 'captured';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'canceled';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PAYEDOUT = 'payedout';
    public const STATUS_UNKNOWN = 'unknown';

    public const ENVIRONMENT_LIVE = 'live';
    public const ENVIRONMENT_TEST = 'test';

    public const METHOD_CARDS = 'cards';
    public const METHOD_PAYMENTS = 'payments';

    public const SOURCE_REQUEST = 'request';
    public const SOURCE_RETURN = 'return';
    public const SOURCE_NOTIFICATION = 'notification';

    /** @var string */
    private $siteId;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $returnUrl;

    /** @var string */
    private $environment;

    /** @var string */
    private $paymentMethod;

    public function __construct(string $siteId, string $apiKey, string $returnUrl, string $environment, string $paymentMethod)
    {
        $this->siteId = $siteId;
        $this->apiKey = $apiKey;
        $this->returnUrl = $returnUrl;
        $this->environment = $environment;
        $this->paymentMethod = $paymentMethod;

        if (!$this->validateEnvironment($environment)) {
            throw new InvalidArgumentException('Wrong Smart2Pay environment.');
        }

        if (!$this->validatePaymentMethod($paymentMethod)) {
            throw new InvalidArgumentException('Wrong Smart2Pay payment method.');
        }

        $this->configureSmart2PaySdk();
    }

    private function validateEnvironment(string $environment): bool
    {
        return in_array($environment, [self::ENVIRONMENT_TEST, self::ENVIRONMENT_LIVE]);
    }

    private function validatePaymentMethod(string $paymentMethod): bool
    {
        return in_array($paymentMethod, [self::METHOD_PAYMENTS, self::METHOD_CARDS]);
    }

    private function configureSmart2PaySdk(): void
    {
        if (!defined('S2P_SDK_SITE_ID')) {
            define('S2P_SDK_SITE_ID', $this->siteId);
        }
        if (!defined('S2P_SDK_API_KEY')) {
            define('S2P_SDK_API_KEY', $this->apiKey);
        }
        if (!defined('S2P_SDK_ENVIRONMENT')) {
            define('S2P_SDK_ENVIRONMENT', $this->environment);
        }
    }

    public function getReturnUrl(Generic $request): string
    {
        $returnUrl = $request->getToken()->getTargetUrl();

        if (!empty($this->returnUrl) && $this->environment === Api::ENVIRONMENT_TEST) {
            $parsedUrl = parse_url($returnUrl);
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
            $returnUrl = trim($this->returnUrl, '/') . $parsedUrl['path'] . $query;
        }

        return $returnUrl;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function authorizeRequest(GetHttpRequest $request): bool
    {
        if (!isset($request->headers)) {
            return false;
        }

        $headers = $request->headers;

        if (!isset($headers['authorization'])) {
            return false;
        }

        if (!(is_array($headers['authorization']) && count($headers['authorization']) === 1)) {
            return false;
        }

        // authorization header should look like 'Authorization: Basic base64_encode($site_id:$api_key)'
        // so we remove first 6 chars from header: 'Basic '
        $authorizationKey = substr($headers['authorization'][0], 6);
        if (!is_string($authorizationKey)) {
            return false;
        }

        $decodedAuthorizationKey = base64_decode($authorizationKey);
        if (!is_string($decodedAuthorizationKey)) {
            return false;
        }

        $decodedAuthorizationKey = explode(':', $decodedAuthorizationKey);
        if (count($decodedAuthorizationKey) !== 2) {
            return false;
        }

        return $decodedAuthorizationKey[0] === $this->siteId && $decodedAuthorizationKey[1] === $this->apiKey;
    }
}
