<?php

namespace spec\BPolNet\SyliusSmart2PayPlugin\Payum\Mapper;

use BPolNet\SyliusSmart2PayPlugin\Payum\Mapper\ApiParameters;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

class ApiParametersSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ApiParameters::class);
    }

    function it_prepare_parameters(
        PaymentInterface $payment,
        OrderInterface $order,
        CustomerInterface $customer
    ): void
    {
        $payment->getOrder()->willReturn($order)->shouldBeCalledOnce();
        $order->getBillingAddress()->willReturn(null);
        $order->getShippingAddress()->willReturn(null);
        $payment->getId()->willReturn(1);
        $order->getTotal()->willReturn(1000);
        $order->getCurrencyCode()->willReturn('EUR');
        $order->getNumber()->willReturn('0000001');
        $order->getCustomer()->willReturn($customer);
        $customer->getId()->willReturn(2);
        $customer->getEmail()->willReturn('email@example.com');
        $customer->getFirstName()->willReturn('First');
        $customer->getLastName()->willReturn('Last');
        $customer->getPhoneNumber()->willReturn('123456789');

        $this->prepare($payment, 'http://example.com/return_url')
            ->shouldBeArray();
    }
}
