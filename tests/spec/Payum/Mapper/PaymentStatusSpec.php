<?php

namespace spec\BPolNet\SyliusSmart2PayPlugin\Payum\Mapper;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;
use BPolNet\SyliusSmart2PayPlugin\Payum\Mapper\PaymentStatus;
use PhpSpec\ObjectBehavior;

class PaymentStatusSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(PaymentStatus::class);
    }

    function it_should_return_success_api_status(): void
    {
        $this->mapFromStatusId(2)->shouldEqual(Api::STATUS_SUCCESS);
    }

    function it_should_return_pending_api_status(): void
    {
        $this->mapFromStatusId(7)->shouldEqual(Api::STATUS_PENDING);
    }

    function it_should_return_unknown_api_status(): void
    {
        $this->mapFromStatusId(100)->shouldEqual(Api::STATUS_UNKNOWN);
        $this->mapFromStatusId(0)->shouldEqual(Api::STATUS_UNKNOWN);
        $this->mapFromStatusId(-20)->shouldEqual(Api::STATUS_UNKNOWN);
    }
}
