<?php

namespace PragmaGoTech\Interview\Service;

use PragmaGoTech\Interview\Model\LoanProposal;

class FeeCalculatorSimple implements FeeCalculator
{
    private array $feeStructure;

    public function __construct(array $feeStructure)
    {
        $this->feeStructure = $feeStructure;
    }

    public function calculate(LoanProposal $loanProposal): float
    {
        $term = $loanProposal->term();
        $amount = $loanProposal->amount();

        if (!isset($this->feeStructure[$term])) {
            throw new \InvalidArgumentException("Unsupported term: $term");
        }

        $structure = $this->feeStructure[$term];

        if (isset($structure[$amount])) {
            return $this->roundFee($amount, $structure[$amount]);
        }

        $lower = null;
        $upper = null;

        foreach ($structure as $key => $value) {
            if ($key <= $amount) $lower = ['amount' => $key, 'fee' => $value];
            if ($key > $amount) {
                $upper = ['amount' => $key, 'fee' => $value];
                break;
            }
        }

        if (!$lower || !$upper) {
            throw new \RuntimeException("Interpolation bounds not found");
        }

        $interpolatedFee = $this->interpolate(
            $amount,
            $lower['amount'],
            $lower['fee'],
            $upper['amount'],
            $upper['fee']
        );

        return $this->roundFee($amount, $interpolatedFee);
    }

    private function interpolate(float $x, float $x1, float $y1, float $x2, float $y2): float
    {
        return $y1 + (($x - $x1) * ($y2 - $y1)) / ($x2 - $x1);
    }

    private function roundFee(float $amount, float $fee): float
    {
        $total = $amount + $fee;
        $roundedTotal = ceil($total / 5) * 5;
        return $roundedTotal - $amount;
    }
}
