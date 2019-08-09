<?php
namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;
use MathPHP\Functions\Polynomial;
use MathPHP\Functions\Support;

class Eigenvalue
{
    const CLOSED_FORM_POLYNOMIAL_ROOT_METHOD = 'closedFormPolynomialRootMethod';
    const POWER_ITERATION = 'powerIteration';

    const METHODS = [
        self::CLOSED_FORM_POLYNOMIAL_ROOT_METHOD,
        self::POWER_ITERATION,
    ];

    /**
     * Is the provided algorithm a valid eigenvalue method?
     *
     * @param  string  $method
     *
     * @return boolean true if a valid method; false otherwise
     */
    public static function isAvailableMethod(string $method): bool
    {
        return in_array($method, self::METHODS);
    }

    /**
     * Verify that the matrix can have eigenvalues
     *
     * @param Matrix $A
     *
     * @throws Exception\BadDataException if the matrix is not square
     */
    private static function checkMatrix(Matrix $A)
    {
        if (!$A->isSquare()) {
            throw new Exception\BadDataException('Matrix must be square');
        }
    }

    /**
     * Produces the Eigenvalues for square 2x2 - 4x4 matricies
     *
     * Given a matrix
     *      [a b]
     * A =  [c d]
     *
     * Find all λ such that:
     *      |A-Iλ| = 0
     *
     * This is accomplished by finding the roots of the polyniomial that
     * is produced when computing the determinant of the matrix. The determinant
     * polynomial is calculated using polynomial arithmetic.
     *
     * @param Matrix $A
     *
     * @return float[] of eigenvalues
     *
     * @throws Exception\BadDataException if the matrix is not square
     * @throws Exception\BadDataException if the matrix is not 2x2, 3x3, or 4x4
     */
    public static function closedFormPolynomialRootMethod(Matrix $A): array
    {
        self::checkMatrix($A);

        $m = $A->getM();
        if ($m < 2 || $m > 4) {
            throw new Exception\BadDataException("Matrix must be 2x2, 3x3, or 4x4. $m x $m given");
        }
        
        // Convert the numerical matrix into an ObjectMatrix
        $B_array = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $B_array[$i][$j] = new Polynomial([$A[$i][$j]], 'λ');
            }
        }
        $B = MatrixFactory::create($B_array);

        // Create a diagonal Matrix of lambda (Iλ)
        $λ_poly    = new Polynomial([1, 0], 'λ');
        $zero_poly = new Polynomial([0], 'λ');
        $λ_array   = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $λ_array[$i][$j] = ($i == $j)
                    ? $λ_poly
                    : $zero_poly;
            }
        }
        /** @var ObjectSquareMatrix $λ */
        $λ = MatrixFactory::create($λ_array);

        /** @var ObjectSquareMatrix Subtract Iλ from B */
        $Bminusλ = $B->subtract($λ);

        /** @var Polynomial The Eigenvalues are the roots of the determinant of this matrix */
        $det = $Bminusλ->det();

        // Calculate the roots of the determinant.
        $eigenvalues = $det->roots();
        return $eigenvalues;
    }

    /**
     * Power Iteration
     *
     * The recurrance relation:
     *         Abₖ
     * bₖ₊₁ = ------
     *        ‖Abₖ‖
     *
     * will converge to the dominant eigenvector,
     *
     * The corresponding eigenvalue is calculated as:
     *
     *      bₖᐪAbₖ
     * μₖ = -------
     *       bₖᐪbₖ
     *
     * https://en.wikipedia.org/wiki/Power_iteration
     * @param Matrix $A
     * @param int $iterations max number of iterations to perform
     *
     * @return float[] most extreme eigenvalue
     * @throws Exception\BadDataException if the matrix is not square
     */
    public static function powerIteration(Matrix $A, int $iterations = 1000): array
    {
        self::checkMatrix($A);
        
        $b = MatrixFactory::random($A->getM(), 1);
        $newμ = 0;
        $μ = -1;
        while (!Support::isEqual($μ, $newμ)) {
            if ($iterations <= 0) {
                throw new Exception\FunctionFailedToConvergeException("Maximum number of iterations excecuted.");
            }
            $μ = $newμ;
            $Ab = $A->multiply($b);
            $b = $Ab->scalarDivide($Ab->frobeniusNorm());
            $newμ = $b->transpose()->multiply($A)->multiply($b)->get(0, 0) / $b->transpose()->multiply($b)->get(0, 0);
            $iterations--;
        }
        
        return [$newμ];
    }
}
