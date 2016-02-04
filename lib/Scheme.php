<?php declare(strict_types = 1);

namespace SSSS;

class Scheme
{
    private $p;

    function __construct(\GMP $p)
    {
        if (!gmp_prob_prime($p, 20)) {
            throw new \InvalidArgumentException('p must be prime.');
        }
        
        $this->p = $p;
    }

    function initialShares(\GMP $secret, int $requiredShares, int $initialShares): array
    {
        if ($requiredShares < 0) {
            throw new \InvalidArgumentException('Required shares must be greater than zero.');
        }

        if ($requiredShares > $initialShares) {
            throw new \InvalidArgumentException('Required shares must be less than or equal to initial shares.');
        }

        if ($initialShares >= $this->p) {
            throw new \InvalidArgumentException('Initial shares must be less than P');
        }

        if ($secret >= $this->p) {
            throw new \InvalidArgumentException('The value of the secret must be less than P');
        }

        $pMinus1 = $this->p - 1;

        $shares = [];
        $coefficients = [$secret];

        for ($shareNumber = 1; $shareNumber < $requiredShares; $shareNumber++) {
            $coefficients[$shareNumber] = gmp_random_range(0, $pMinus1);
        }

        for($shareNumber = 1; $shareNumber <= $initialShares; $shareNumber++) {
            $shares[$shareNumber] = $this->generateShare($shareNumber, $coefficients);
        }

        return $shares;
    }

    private function generateShare(int $shareNumber, array $coefficients): Share
    {
        $share = $coefficients[0]; // coefficient for exponent 0 is the secret
        $numCoefficients = count($coefficients);

        for ($exponent = 1; $exponent < $numCoefficients; $exponent++) {
            $share = ($share + ($coefficients[$exponent] * ($shareNumber ** $exponent))) % $this->p;
        }

        return new Share($shareNumber, $share);
    }

    private function validateArrayOfShares(array $shares)
    {
        array_walk($shares, function($share) {
            if (!$share instanceof Share) {
                throw new \InvalidArgumentException('Shares must be an array of Share objects.');
            }
        });
    }

    function recoverSecret(array $shares): \GMP
    {
        $this->validateArrayOfShares($shares);

        return FiniteFieldLagrange::recoverFreeCoefficient($this->p, $shares);
    }

    function addShare(int $shareNumber, array $shares): Share
    {
        $this->validateArrayOfShares($shares);

        $coefficients = FiniteFieldLagrange::recoverCoefficients($this->p, $shares);

        return $this->generateShare($shareNumber, $coefficients);
    }
}
