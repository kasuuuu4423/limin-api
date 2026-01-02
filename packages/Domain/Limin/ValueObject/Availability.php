<?php

declare(strict_types=1);

namespace Domain\Limin\ValueObject;

enum Availability: string
{
    case NOW = 'NOW';
    case LATER = 'LATER';
    case BLOCKED = 'BLOCKED';
}
