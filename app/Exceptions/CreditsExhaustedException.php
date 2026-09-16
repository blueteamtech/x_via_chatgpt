<?php

namespace App\Exceptions;

use RuntimeException;

class CreditsExhaustedException extends RuntimeException
{
    public function __construct(
        public readonly string $tier,
        public readonly int $usedCredits,
        public readonly int $allowance,
    ) {
        parent::__construct(
            "You have used {$usedCredits} of your {$allowance} monthly credits on the {$tier} plan. Top up or upgrade to continue. Credits reset on the 1st."
        );
    }
}
