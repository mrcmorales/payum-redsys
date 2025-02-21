<?php

namespace MrcMorales\Payum\Redsys;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;

class Api
{
    public const SIGNATURE_VERSION = 'HMAC_SHA256_V1';
    private const ORDER_NUMBER_MINIMUM_LENGTH = 4;
    private const ORDER_NUMBER_MAXIMUM_LENGTH = 12;

    public const DS_RESPONSE_CANCELED = '0184';
    public const DS_RESPONSE_USER_CANCELED = '9915';

    /**
     * Currency codes to the values the bank
     * understand. Remember you can only work
     * with one of them per commerce.
     */
    protected array $currencies = [
        'EUR' => '978',
        'USD' => '840',
        'GBP' => '826',
        'JPY' => '392',
        'ARA' => '32',
        'CAD' => '124',
        'CLP' => '152',
        'COP' => '170',
        'INR' => '356',
        'MXN' => '484',
        'PEN' => '604',
        'CHF' => '756',
        'BRL' => '986',
        'VEF' => '937',
        'TRL' => '949',
    ];

    public function __construct(private array $options)
    {
        $this->options = array_replace($this->options, $options);

        if (true == empty($this->options['merchant_code'])) {
            throw new InvalidArgumentException('The merchant_code option must be set.');
        }
        if (true == empty($this->options['terminal'])) {
            throw new InvalidArgumentException('The terminal option must be set.');
        }
        if (true == empty($this->options['secret_key'])) {
            throw new InvalidArgumentException('The secret_key option must be set.');
        }
        if (false == is_bool($this->options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }
    }

    public function getApiEndpoint(): string
    {
        return $this->options['sandbox'] ?
            'https://sis-t.redsys.es:25443/sis/realizarPago' :
            'https://sis.redsys.es/sis/realizarPago';
    }

    /**
     * Validate the order number passed to the bank. it needs to pass the
     * following test.
     *
     * - Must be between 4 and 12 characters
     *     - We complete with 0 to the left in case length or the number is lower
     *       than 4 in order to make the integration easier
     * - Four first characters must be digits
     * - Following eight can be digits or characters which ASCII numbers are:
     *    - between 65 and 90 ( A - Z)
     *    - between 97 and 122 ( a - z )
     *
     * If the test pass, orderNumber will be returned. if not, a Exception will be thrown
     */
    public function ensureCorrectOrderNumber(string $orderNumber): string
    {
        if (strlen($orderNumber) > self::ORDER_NUMBER_MAXIMUM_LENGTH) {
            throw new LogicException(sprintf('Payment number can\'t have more than %d characters', self::ORDER_NUMBER_MAXIMUM_LENGTH));
        }

        $normalizedOrderNumber = str_pad(
            $orderNumber,
            self::ORDER_NUMBER_MINIMUM_LENGTH,
            '0',
            STR_PAD_LEFT
        );

        if (!preg_match('/^[0-9]{4}[a-z0-9]{0,12}$/i', $normalizedOrderNumber)) {
            throw new LogicException('The payment gateway doesn\'t allow order numbers with this format.');
        }

        return $normalizedOrderNumber;
    }

    public function getISO4127($currency): string
    {
        if (!isset($this->currencies[$currency])) {
            throw new LogicException('Currency not allowed by the gateway.');
        }

        return $this->currencies[$currency];
    }

    public function getMerchantCode(): string
    {
        return $this->options['merchant_code'];
    }

    public function getMerchantTerminalCode(): string
    {
        return $this->options['terminal'];
    }

    public function validateNotificationSignature(array $notification): bool
    {
        $notification = ArrayObject::ensureArrayObject($notification);
        $notification->validateNotEmpty(['Ds_Signature', 'Ds_MerchantParameters']);
        $signedResponse = $this->createMerchantSignatureNotify($this->options['secret_key'], $notification['Ds_MerchantParameters']);

        return $signedResponse === $notification['Ds_Signature'];
    }

    public function sign(array $params): string
    {
        $base64DecodedKey = base64_decode($this->options['secret_key']);
        $key = $this->encrypt_3DES(
            $params['Ds_Merchant_Order'],
            $base64DecodedKey
        );

        $res = $this->mac256(
            $this->createMerchantParameters($params),
            $key
        );

        return base64_encode($res);
    }

    public function createMerchantParameters(array $params): string
    {
        return $this->encodeBase64(json_encode($params));
    }

    private function createMerchantSignatureNotify(string $key, string $data): string
    {
        $key = $this->decodeBase64($key);
        $orderData = json_decode($this->base64_url_decode($data), true);
        $key = $this->encrypt_3DES($orderData['Ds_Order'], $key);
        $res = $this->mac256($data, $key);

        return $this->base64_url_encode($res);
    }

    private function encrypt_3DES(string $message, string $key): false|string
    {
        $l = ceil(strlen($message) / 8) * 8;

        return substr(
            openssl_encrypt(
                $message.str_repeat("\0", $l - strlen($message)),
                'des-ede3-cbc',
                $key,
                OPENSSL_RAW_DATA,
                "\0\0\0\0\0\0\0\0"
            ),
            0,
            $l
        );
    }

    private function base64_url_encode(string $input): string
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    private function encodeBase64(string $data): string
    {
        return base64_encode($data);
    }

    private function base64_url_decode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private function decodeBase64($data): string
    {
        return base64_decode($data);
    }

    private function mac256($ent, $key): string
    {
        return hash_hmac('sha256', $ent, $key, true);
    }
}
