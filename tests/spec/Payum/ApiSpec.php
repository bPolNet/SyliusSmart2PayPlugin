<?php

namespace spec\BPolNet\SyliusSmart2PayPlugin\Payum;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use Payum\Core\Request\GetHttpRequest;
use PhpSpec\ObjectBehavior;

class ApiSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(
            '99999',
            '1234567890abcdef',
            '',
            Api::ENVIRONMENT_LIVE,
            API::METHOD_PAYMENTS
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Api::class);
    }

    function it_authorize_request(GetHttpRequest $httpRequest): void
    {
        $httpRequest->headers = [
            'authorization' => ['Basic OTk5OTk6MTIzNDU2Nzg5MGFiY2RlZg==']
        ];
        $this->authorizeRequest($httpRequest)->shouldReturn(true);
    }

    function it_not_authorize_request(GetHttpRequest $httpRequest): void
    {
        $this->authorizeRequest($httpRequest)->shouldReturn(false);

        $httpRequest->headers = [];
        $this->authorizeRequest($httpRequest)->shouldReturn(false);

        $httpRequest->headers = [
            'cookie' => ['cookie=yes']
        ];
        $this->authorizeRequest($httpRequest)->shouldReturn(false);

        $httpRequest->headers = [
            'authorization' => []
        ];
        $this->authorizeRequest($httpRequest)->shouldReturn(false);

        $httpRequest->headers = [
            'authorization' => [ 'wrong_authorization_header' ]
        ];
        $this->authorizeRequest($httpRequest)->shouldReturn(false);
    }

    function it_returns_payment_method(): void
    {
        $this->getPaymentMethod()->shouldReturn(API::METHOD_PAYMENTS);
    }
}
