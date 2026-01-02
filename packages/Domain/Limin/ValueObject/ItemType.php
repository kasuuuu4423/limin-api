<?php

declare(strict_types=1);

namespace Domain\Limin\ValueObject;

enum ItemType: string
{
    case MESSAGE = 'message';
    case TASK = 'task';
}
