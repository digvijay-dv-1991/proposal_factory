<?php

namespace App\Enums;

enum OtpResendResult
{
    case Sent;
    case RateLimited;
}
