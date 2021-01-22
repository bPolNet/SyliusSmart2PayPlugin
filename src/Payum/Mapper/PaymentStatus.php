<?php
declare(strict_types=1);

namespace BPolNet\SyliusSmart2PayPlugin\Payum\Mapper;

use BPolNet\SyliusSmart2PayPlugin\Payum\Api;

class PaymentStatus
{
    // notification statuses taken from
    // https://docs.smart2pay.com/category/payments-api/payment-notification/payment-notification-format/
    // https://docs.smart2pay.com/category/status-codes/globalpay-status-codes/
    // https://docs.smart2pay.com/category/status-codes/card-processing-status-codes/
    private const MAPPINGS = [
        // statusId => Api::status // Status: Description. YES/NO (is final status)
        1 => Api::STATUS_PENDING,
        2 => Api::STATUS_SUCCESS,
        3 => Api::STATUS_CANCELLED,
        4 => Api::STATUS_FAILED,
        5 => Api::STATUS_EXPIRED,
        6 => Api::STATUS_PENDING,
        7 => Api::STATUS_PENDING,
        8 => Api::STATUS_PENDING,
        9 => Api::STATUS_AUTHORIZED,
        10 => Api::STATUS_PENDING,
        11 => Api::STATUS_CAPTURED,
        12 => Api::STATUS_FAILED,
        13 => Api::STATUS_CAPTURED, // CaptureRequested: The payment was successfully authorized and the capture request was also sent to the provider. The goods can be delivered.
        14 => Api::STATUS_SUSPENDED, // Exception: The transaction needs manual review from Smart2Pay.
        15 => Api::STATUS_CANCELLED, // CancelRequested: The cancel request has been sent.
        16 => Api::STATUS_CANCELLED,
        17 => Api::STATUS_CAPTURED, // Completed: The transaction has been completed by the customer. NO
        18 => Api::STATUS_PENDING,
        19 => Api::STATUS_PENDING,
        21 => Api::STATUS_REFUNDED, // PartiallyRefunded: The payment has been partially refunded with a smaller amount than the one from the initial paid transaction. YES
        22 => Api::STATUS_REFUNDED,
        23 => Api::STATUS_CAPTURED, // DisputeWon: The cardholder has lost the dispute and the money will return to the merchant. YES
        24 => Api::STATUS_REFUNDED, // DisputeLost: The cardholder has won the dispute and has received the money back. YES
        25 => Api::STATUS_CAPTURED,
        26 => Api::STATUS_REFUNDED, // Chargedback: The cardholder has won the dispute and has received the money back. YES
        27 => Api::STATUS_CAPTURED, // SecondChargebackWon: The cardholder has lost the second dispute and the money will return to the merchant. YES
        28 => Api::STATUS_REFUNDED, // SecondDisputeLost: The cardholder has won the second dispute and has received the money back.
        30 => Api::STATUS_AUTHORIZED, // PendingChallengeConfirmation: The fraud provider has challenged the payment. Payment is authorized. You can reject or accept the challenge. NO
        33 => Api::STATUS_CAPTURED,
        34 => Api::STATUS_CANCELLED,
        35 => Api::STATUS_CAPTURED, // PartiallyCaptured: The payment is partially captured. YES
        36 => Api::STATUS_PENDING, // SuccessWaitForFraud NO
        37 => Api::STATUS_PENDING,
        38 => Api::STATUS_CANCELLED, // Retrieval: Retrieval request indicates that the customer wants more information about a transaction and inquires their issuing bank and asks for additional information on the charge, but hasn’t yet initiated a chargeback. YES
        39 => Api::STATUS_CANCELLED, // SoftDeclined: A soft decline may occur when the issuing bank will not proceed with a transaction that require SCA and doesn’t meet these requirements YES
    ];

    public function mapFromStatusId(int $statusId): string
    {
        if (!array_key_exists($statusId, self::MAPPINGS)) {
            return Api::STATUS_UNKNOWN;
        }

        return self::MAPPINGS[$statusId];
    }
}
