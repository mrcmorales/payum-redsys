<?php

namespace MrcMorales\Payum\Redsys\Action;

use MrcMorales\Payum\Redsys\Action\Api\BaseApiAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use MrcMorales\Payum\Redsys\Api;

class CaptureAction extends BaseApiAwareAction implements ActionInterface
{
    use GatewayAwareTrait;

    /** @var Api */
    protected $api;


    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var  $postData */
        $postData = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($postData['Ds_Merchant_MerchantURL']) && $request->getToken()) {
            $postData['Ds_Merchant_MerchantURL'] = $request->getToken()->getTargetUrl();
        }

        $postData->validatedKeysSet(array(
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantURL',
        ));

        if (false === $postData['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlOK'] = $request->getToken()
                ->getTargetUrl();
        }
        if (false === $postData['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlKO'] = $request->getToken()
                ->getTargetUrl();
        }

        $details['Ds_Merchant_TransactionType'] = $this->api::TRANSACTIONTYPE_AUTHORIZATION;
        $details['Ds_SignatureVersion'] = Api::SIGNATURE_VERSION;
        $details['Ds_MerchantParameters'] = $this->api->createMerchantParameters($postData->toUnsafeArray());
        $details['Ds_Signature'] = $this->api->sign($postData->toUnsafeArray());

        throw new HttpPostRedirect($this->api->getApiEndpoint(), $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}


