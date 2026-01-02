<?php

declare(strict_types=1);

namespace Domain\Limin\ValueObject;

enum ItemState: string
{
    case DO = 'DO';
    case WAIT = 'WAIT';
    case HOLD = 'HOLD';
}
