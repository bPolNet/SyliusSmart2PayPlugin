<?php

namespace spec\BPolNet\SyliusSmart2PayPlugin\Payum\Action;

use BPolNet\SyliusSmart2PayPlugin\Payum\Action\StatusAction;
use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use PhpSpec\ObjectBehavior;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

class StatusActionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(StatusAction::class);
    }

    function it_marks_as_captured_when_status_is_captured(
        GetStatus $request,
        PaymentInterface $payment
    ): void
    {
        $details = ['status' => Api::STATUS_CAPTURED];
        $request->getFirstModel()->willReturn($payment);
        $payment->getDetails()->willReturn($details);

        $request->markCaptured()->shouldBeCalled();
        $request->markRefunded()->shouldNotBeCalled();

        $this->execute($request);
    }

    function it_marks_as_failed_when_status_is_failed(
        GetStatus $request,
        PaymentInterface $payment
    ): void
    {
        $details = ['status' => Api::STATUS_FAILED];
        $request->getFirstModel()->willReturn($payment);
        $payment->getDetails()->willReturn($details);

        $request->markFailed()->shouldBeCalled();

        $this->execute($request);
    }

    function it_marks_as_unknown_when_status_is_unknown(
        GetStatus $request,
        PaymentInterface $payment
    ): void
    {
        $details = ['status' => Api::STATUS_UNKNOWN];
        $request->getFirstModel()->willReturn($payment);
        $payment->getDetails()->willReturn($details);

        $request->markUnknown()->shouldBeCalled();

        $this->execute($request);
    }

    function it_marks_as_unknown_when_status_is_not_present(
        GetStatus $request,
        PaymentInterface $payment
    ): void
    {
        $details = [];
        $request->getFirstModel()->willReturn($payment);
        $payment->getDetails()->willReturn($details);

        $request->markUnknown()->shouldBeCalled();

        $this->execute($request);
    }
}
