<?php

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Services\DecisionRules\GuardBand;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;
use Modules\Metrology\Services\DecisionRules\UncertaintyAccounted;

test('simple acceptance rule evaluates correctly', function () {
    $strategy = new SimpleAcceptance();
    
    // Error (0.05) <= Limit (0.05) -> Pass
    expect($strategy->evaluate(0.05, 0.02, 0.05))->toBeTrue();
    
    // Error (0.06) > Limit (0.05) -> Fail
    expect($strategy->evaluate(0.06, 0.02, 0.05))->toBeFalse();
});

test('uncertainty accounted rule evaluates correctly', function () {
    $strategy = new UncertaintyAccounted();
    
    // Error (0.04) + Uncertainty (0.02) = 0.06 > Limit (0.05) -> Fail
    expect($strategy->evaluate(0.04, 0.02, 0.05))->toBeFalse();
    
    // Error (0.02) + Uncertainty (0.02) = 0.04 <= Limit (0.05) -> Pass
    expect($strategy->evaluate(0.02, 0.02, 0.05))->toBeTrue();
});

test('guard band rule evaluates correctly', function () {
    $strategy = new GuardBand(); // Multiplier 1.0 default
    
    // Limit (0.05) - Uncertainty (0.02) = Reduced Limit (0.03)
    
    // Error (0.03) <= Reduced (0.03) -> Pass
    expect($strategy->evaluate(0.03, 0.02, 0.05))->toBeTrue();
    
    // Error (0.04) > Reduced (0.03) -> Fail
    expect($strategy->evaluate(0.04, 0.02, 0.05))->toBeFalse();
});
