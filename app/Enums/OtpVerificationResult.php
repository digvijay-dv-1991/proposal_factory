<?php

namespace App\Enums;

enum OtpVerificationResult
{
    case Success;
    case InvalidCode;
    case Expired;
    case TooManyAttempts;
    case NoChallenge;
}
