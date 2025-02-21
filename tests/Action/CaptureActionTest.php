<?php

namespace MrcMorales\Payum\Redsys\Tests\Action;

use MrcMorales\Payum\Redsys\Action\CaptureAction;
use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class CaptureActionTest extends GenericActionTest
{
    protected $requestClass = Capture::class;
    protected $actionClass = CaptureAction::class;

    #[Test]
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    #[Test]
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    #[Test]
    public function shouldThrowIfAmountIsNotSet()
    {
        $this->expectExceptionMessage("The Ds_Merchant_Amount fields is not set.");
        $this->expectException(LogicException::class);
        $model = array();

        $details = ArrayObject::ensureArrayObject($model);

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->with($details)
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }



    #[Test]
    public function shouldThrowIfOrderNumberIsNotSet()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The Ds_Merchant_Order fields is not set.");
        $model = array(
            'Ds_Merchant_Amount' => 1000
        );

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }



    #[Test]
    public function shouldThrowIfCurrencyIsNotSet()
    {
        $this->expectExceptionMessage("The Ds_Merchant_Currency fields is not set.");
        $this->expectException(LogicException::class);
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234'
        );

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }


    #[Test]
    public function shouldThrowIfTransactionTypeIsNotSet()
    {
        $this->expectExceptionMessage("The Ds_Merchant_TransactionType fields is not set.");
        $this->expectException(LogicException::class);
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978'
        );

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }


    #[Test]
    public function shouldThrowIfMerchantURLIsNotSet()
    {
        $this->expectExceptionMessage("The Ds_Merchant_MerchantURL fields is not set.");
        $this->expectException(LogicException::class);
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978',
            'Ds_Merchant_TransactionType' => 0
        );

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->will($this->throwException(new LogicException()))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }



    #[Test]
    public function shouldRedirectToRedsysSite()
    {
        $this->expectException(HttpPostRedirect::class);
        $model = array(
            'Ds_Merchant_Amount' => 1000,
            'Ds_Merchant_Order' => '1234',
            'Ds_Merchant_Currency' => '978',
            'Ds_Merchant_TransactionType' => 0,
            'Ds_Merchant_MerchantURL' => 'https://sis-t.sermepa.es:25443/sis/realizarPago'
        );

        $apiMock = $this->createApiMock();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->never())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
        ;

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $request = new Capture($model);
        $action->execute($request);
    }

    protected function createApiMock(): MockObject|Api
    {
        return $this->createMock( Api::class);
    }


    protected function createGatewayMock(): MockObject|GatewayInterface
    {
        return $this->createMock(GatewayInterface::class);
    }
}
