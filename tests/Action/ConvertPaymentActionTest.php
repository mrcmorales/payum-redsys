<?php

namespace MrcMorales\Payum\Redsys\Tests\Action;

use MrcMorales\Payum\Redsys\Action\ConvertPaymentAction;
use MrcMorales\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Tests\GenericActionTest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class ConvertPaymentActionTest extends GenericActionTest
{
    protected $actionClass = ConvertPaymentAction::class;
    protected $requestClass = Convert::class;

    public function provideSupportedRequests() : \Iterator
    {

        yield  array(new $this->requestClass(new Payment(), 'array'));
        yield  array(new $this->requestClass($this->createMock(PaymentInterface::class), 'array'));
        yield  array(new $this->requestClass(new Payment(), 'array', $this->createMock(TokenInterface::class)));
    }

    public function provideNotSupportedRequests(): \Iterator
    {
        yield array('foo');
        yield array(array('foo'));
        yield array(new \stdClass());
        yield array($this->createMock(Generic::class, array(array())));
        yield array(new $this->requestClass(new Payment(), 'notArray'));
    }

     protected function createApiMock(): MockObject|Api
    {
        return $this->createMock( Api::class, array(), array(), '', false );
    }

    #[Test]
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass(ConvertPaymentAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    #[Test]
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();
        $action = new ConvertPaymentAction();
        $action->setApi($expectedApi);

        $reflection = new \ReflectionClass(ConvertPaymentAction::class);
        $property = $reflection->getProperty('api');
        $this->assertSame($expectedApi, $property->getValue($action));
    }

    #[Test]
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack()
    {
        $payment = new Payment;
        $payment->setNumber('1234');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $payment->setDetails(array(
            'Ds_Merchant_MerchantURL' => 'a_merchant_url'
        ));

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('ensureCorrectOrderNumber')
            ->with($payment->getNumber())
            ->willReturn($payment->getNumber())
        ;

        $apiMock
            ->expects($this->once())
            ->method('getISO4127')
            ->with($payment->getCurrencyCode())
            ->willReturn('840');

        $apiMock
            ->expects($this->once())
            ->method('getMerchantCode')
            ->willReturn('a_merchant_code')
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantTerminalCode')
            ->willReturn('001')
        ;

        $tokenMock = $this->createMock(TokenInterface::class);

        $action = new ConvertPaymentAction();
        $action->setApi($apiMock);
        $action->execute($convert = new Convert($payment, 'array', $tokenMock));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('Ds_Merchant_Amount', $details);
        $this->assertEquals(123, $details['Ds_Merchant_Amount']);

        $this->assertArrayHasKey('Ds_Merchant_Order', $details);
        $this->assertEquals('1234', $details['Ds_Merchant_Order']);

        $this->assertArrayHasKey('Ds_Merchant_Currency', $details);
        $this->assertEquals(840, $details['Ds_Merchant_Currency']);

        $this->assertArrayHasKey('Ds_Merchant_MerchantCode', $details);
        $this->assertEquals('a_merchant_code', $details['Ds_Merchant_MerchantCode']);

        $this->assertArrayHasKey('Ds_Merchant_Terminal', $details);
        $this->assertEquals('001', $details['Ds_Merchant_Terminal']);

        $this->assertArrayHasKey('Ds_Merchant_MerchantURL', $details);
        $this->assertEquals('a_merchant_url', $details['Ds_Merchant_MerchantURL']);
    }

    #[Test]
    public function shouldNotOverrideProvidesValue()
    {
        $payment = new Payment;
        $payment->setNumber('1234');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $payment->setDetails(array(
            'Ds_Merchant_MerchantURL' => 'a_merchant_url',
            'Ds_Merchant_TransactionType' => 1,
            'Ds_Merchant_ConsumerLanguage' => '002'
        ));

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('ensureCorrectOrderNumber')
            ->with($payment->getNumber())
            ->willReturn($payment->getNumber())
        ;

        $apiMock
            ->expects($this->once())
            ->method('getISO4127')
            ->with($payment->getCurrencyCode())
            ->willReturn('840')
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantCode')
            ->willReturn('a_merchant_code')
        ;

        $apiMock
            ->expects($this->once())
            ->method('getMerchantTerminalCode')
            ->willReturn('001')
        ;

        $tokenMock = $this->createMock(TokenInterface::class);

        $action = new ConvertPaymentAction();
        $action->setApi($apiMock);
        $action->execute(new Convert($payment, 'array', $tokenMock));
        $details = $payment->getDetails();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('Ds_Merchant_MerchantURL', $details);
        $this->assertEquals('a_merchant_url', $details['Ds_Merchant_MerchantURL']);

        $this->assertArrayHasKey('Ds_Merchant_TransactionType', $details);
        $this->assertEquals(1, $details['Ds_Merchant_TransactionType']);

        $this->assertArrayHasKey('Ds_Merchant_ConsumerLanguage', $details);
        $this->assertEquals('002', $details['Ds_Merchant_ConsumerLanguage']);
    }



    #[Test]
    public function throwIfUnsupportedApiGiven()
    {
        $this->expectException(UnsupportedApiException::class);
        $action = new ConvertPaymentAction();

        $action->setApi(new \stdClass);
    }
}
