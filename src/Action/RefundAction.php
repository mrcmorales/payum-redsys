<?php

namespace MrcMorales\Payum\Redsys\Action;

use GuzzleHttp\Client;
use MrcMorales\Payum\Redsys\Action\Api\BaseApiAwareAction;
use MrcMorales\Payum\Redsys\Api;
use MrcMorales\Payum\Redsys\Util\TransactionType;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Refund;

class RefundAction  extends BaseApiAwareAction implements ActionInterface
{
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;

    /**
     * @param Refund $request
     */
    public function execute($request): ArrayObject
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $postData = ArrayObject::ensureArrayObject($request->getModel());

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        $postData->defaults([
            'Ds_Merchant_Amount' => $payment->getTotalAmount(),
            'Ds_Merchant_Order' => $this->api->ensureCorrectOrderNumber($payment->getNumber()),
            'Ds_Merchant_MerchantCode' => $this->api->getMerchantCode(),
            'Ds_Merchant_Currency' => $this->api->getISO4127($payment->getCurrencyCode()),
            'Ds_Merchant_Terminal' => $this->api->getMerchantTerminalCode(),
            'Ds_Merchant_TransactionType' => TransactionType::REFUND,
        ]);

        $postData->validatedKeysSet([
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_Terminal',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantCode',
        ]);


        $details['Ds_SignatureVersion'] = Api::SIGNATURE_VERSION;
        $details['Ds_MerchantParameters'] = $this->api->createMerchantParameters($postData->toUnsafeArray());
        $details['Ds_Signature'] = $this->api->sign($postData->toUnsafeArray());

        $client = new Client();
        $response = $client->post($this->api->getRestEndpoint(),
            [
                'json' => $details,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        
        $response = json_decode($response->getBody()->getContents(), true);
        
        if (array_key_exists('errorCode', $response)) {            
            throw new RequestNotSupportedException($response['errorCode']);
        }       
        
        if (false === array_key_exists('Ds_Signature', $response)) {

            throw new RequestNotSupportedException('Ds_Signature missing in response');
        }

        if (false === array_key_exists('Ds_MerchantParameters', $response)) {
            throw new RequestNotSupportedException('Ds_MerchantParameters missing in response parameters missing');
        }

        if (false === $this->api->validateSignature($response)) {
            throw new RequestNotSupportedException('Signature is invalid');
        }

        $postData->replace(
            ArrayObject::ensureArrayObject(
                json_decode(base64_decode(strtr($response['Ds_MerchantParameters'], '-_', '+/')))
            )->toUnsafeArray() +
            $response
        );

        return $postData;
    }

    public function supports($request): bool
    {
        return
            $request instanceof Refund
            && $request->getModel() instanceof \ArrayAccess
        ;
    }
}
