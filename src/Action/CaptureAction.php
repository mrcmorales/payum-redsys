<?php

namespace MrcMorales\Payum\Redsys\Action;

use MrcMorales\Payum\Redsys\Action\Api\BaseApiAwareAction;
use MrcMorales\Payum\Redsys\Api;
use MrcMorales\Payum\Redsys\Util\TransactionType;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

class CaptureAction extends BaseApiAwareAction implements ActionInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /** @var Api */
    protected $api;

    /**
     * @param Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $postData = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($postData['Ds_Merchant_MerchantURL']) && $request->getToken()) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );
            $postData['Ds_Merchant_MerchantURL'] = $notifyToken->getTargetUrl();
        }

        $postData->validatedKeysSet([
            'Ds_Merchant_Amount',
            'Ds_Merchant_Order',
            'Ds_Merchant_Currency',
            'Ds_Merchant_TransactionType',
            'Ds_Merchant_MerchantURL',
        ]);

        $postData['Ds_Merchant_TransactionType'] = TransactionType::AUTHORIZATION;

        if (!$postData['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlOK'] = $request->getToken()
                ->getAfterUrl();
        }

        if (!$postData['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $postData['Ds_Merchant_UrlKO'] = $request->getToken()
                ->getAfterUrl();
        }

        $details['Ds_SignatureVersion'] = Api::SIGNATURE_VERSION;
        $details['Ds_MerchantParameters'] = $this->api->createMerchantParameters($postData->toUnsafeArray());
        $details['Ds_Signature'] = $this->api->sign($postData->toUnsafeArray());

        throw new HttpPostRedirect($this->api->getApiEndpoint(), $details);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof \ArrayAccess;
    }
}
