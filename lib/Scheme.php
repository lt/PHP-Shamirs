<?php

namespace SSSS;

class Scheme
{
    private $p;

    function __construct(\GMP $p)
    {
        $this->p = $p;
    }

    function initialShares(\GMP $secret, $k, $n)
    {
        $pMinus1 = $this->p - 1;

        $shares = [];
        $coefficients = [$secret];

        for ($i = 1; $i < $k; $i++) {
            $coefficients[$i] = gmp_random_range(0, $pMinus1);
        }

        for($i = 1; $i <= $n; $i++) {
            $shares[$i] = $this->generateShare($i, $coefficients);
        }

        return $shares;
    }

    private function generateShare($i, array $coefficients)
    {
        $share = $coefficients[0]; // coefficient for exponent 0 is the secret
        $numCoefficients = count($coefficients);

        for ($exp = 1; $exp < $numCoefficients; $exp++) {
            $share = ($share + ($coefficients[$exp] * ($i ** $exp))) % $this->p;
        }

        return $share;
    }

    function recoverSecret(array $shares)
    {
        $secret = 0;

        // Fast lagrange to recover free coefficient only
        foreach ($shares as $xA => $yA) {
            $numerator = 1;
            $denominator = 1;

            foreach ($shares as $xB => $yB) {
                if($xA == $xB) {
                    continue;
                }

                $numerator = ($numerator * -$xB) % $this->p;
                $denominator = ($denominator * ($xA - $xB)) % $this->p;
            }

            $secret = ($secret + $this->p + ($yA * $numerator * gmp_invert($denominator, $this->p))) % $this->p;
        }

        return $secret;
    }

    function addShare($i, array $shares)
    {
        $polynomials  = $this->generatePolynomials($shares);
        $polynomials  = $this->expandPolynomials($polynomials);
        $coefficients = $this->reducePolynomials($polynomials);

        return $this->generateShare($i, $coefficients);
    }

    private function generatePolynomials(array $shares): array
    {
        $polynomials = [];

        foreach ($shares as $xA => $yA) {
            $obj = new \stdClass();
            $obj->x = $xA;
            $obj->y = $yA;
            $obj->numerators = [];
            $obj->denominator = 1;

            foreach ($shares as $xB => $yB) {
                if($xA == $xB) {
                    continue;
                }

                $obj->numerators[] = -$xB;
                $obj->denominator *= ($obj->x - $xB);
            }

            $polynomials[] = $obj;
        }

        return $polynomials;
    }

    private function expandPolynomials(array $poly): array
    {
        foreach ($poly as $obj) {
            $numNumerators = count($obj->numerators);
            $obj->expanded = [$numNumerators => 1];
            $stack = [[1, 0, $numNumerators - 1]];
            $sp = 0;

            do {
                list($base, $index, $depth) = $stack[$sp--];

                while ($index < $numNumerators) {
                    $numerator = $obj->numerators[$index] * $base;
                    $obj->expanded[$depth] = ($obj->expanded[$depth] ?? 0) + $numerator;

                    $stack[++$sp] = [$numerator, ++$index, $depth - 1];
                }
            } while ($sp >= 0);
        }

        return $poly;
    }

    private function reducePolynomials(array $poly): array
    {
        $result = [];

        foreach ($poly as $obj) {
            foreach ($obj->expanded as $k => $v) {
                $result[$k] = (($result[$k] ?? 0) + $this->p + ($obj->y * $v * gmp_invert($obj->denominator, $this->p))) % $this->p;
            }
        }

        return $result;
    }
}
