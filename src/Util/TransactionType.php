<?php

declare(strict_types=1);

namespace MrcMorales\Payum\Redsys\Util;

final class TransactionType
{
    public const AUTHORIZATION = 0;
    public const PRE_AUTHORIZATION = 1;
    public const CONFIRMATION = 2;
    public const REFUND = 3;
    public const CANCELLATION = 9;
}
