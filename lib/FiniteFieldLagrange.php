<?php declare(strict_types = 1);

namespace SSSS;

abstract class FiniteFieldLagrange
{
    /**
     * Optimised Lagrange to recover the free coefficient only
     *
     * @param \GMP $field
     * @param array[\SSSS\Share] $shares
     * @return \GMP
     */
    static function recoverFreeCoefficient(\GMP $field, array $shares): \GMP
    {
        $freeCoefficient = 0;

        // Fast lagrange to recover free coefficient only
        foreach ($shares as $shareA) {
            $xA = $shareA->number();
            $yA = $shareA->value();
            $numerator = 1;
            $denominator = 1;

            foreach ($shares as $shareB) {
                $xB = $shareB->number();
                if($xA === $xB) {
                    continue;
                }

                $numerator = ($numerator * -$xB) % $field;
                $denominator = ($denominator * ($xA - $xB)) % $field;
            }

            $freeCoefficient = ($freeCoefficient + $field + ($yA * $numerator * gmp_invert($denominator, $field))) % $field;
        }

        return $freeCoefficient;
    }

    /**
     * Full Lagrange interpolation to recover all coefficients
     *
     * @param \GMP $field
     * @param array[\SSSS\Share] $shares
     * @return array
     */
    static function recoverCoefficients(\GMP $field, array $shares): array
    {
        $coefficients = [];

        foreach ($shares as $shareA) {
            $xA = $shareA->number();
            $yA = $shareA->value();

            $numerators = [];
            $denominator = 1;

            foreach ($shares as $shareB) {
                $xB = $shareB->number();
                if ($xA == $xB) {
                    continue;
                }

                $numerators[] = -$xB;
                $denominator *= ($xA - $xB);
            }

            // Perform polynomial expansion.
            // i.e.  (x+1)(x+2)(x+3) => ax^3 + bx^2 + cx + d
            $numNumerators = count($numerators);
            $expanded = [$numNumerators => 1];
            $stack = [[1, 0, $numNumerators - 1]];
            $stackPointer = 0;

            do {
                list($base, $index, $depth) = $stack[$stackPointer--];

                while ($index < $numNumerators) {
                    $numerator = $numerators[$index] * $base;
                    $expanded[$depth] = ($expanded[$depth] ?? 0) + $numerator;

                    $stack[++$stackPointer] = [$numerator, ++$index, $depth - 1];
                }
            } while ($stackPointer >= 0);

            // Solve expanded polynomials
            foreach ($expanded as $coefficient => $value) {
                $coefficients[$coefficient] = (($coefficients[$coefficient] ?? 0) + ($yA * $value * gmp_invert($denominator, $field))) % $field;
            }
        }

        return $coefficients;
    }
}
