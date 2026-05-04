<?php

namespace App\Enums;

enum NotificationStyles: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
    case NEUTRAL = 'neutral';
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
}
