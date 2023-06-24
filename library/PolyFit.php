<?php
/**
 * Class LinearRegression
 *
 * Linear model that uses least squares method to approximate solution.
 *
 * @package MachineLearning
 */
class PolyFit
{

    /**
     * @var float[]
     */
    private $xCoords = [];

    /**
     * @var float[]
     */
    private $yCoords = [];

    /**
     * Holds the y differences from the calculated regression line
     *
     * @var float[]
     */
    private $yDifferences = [];


    /**
     * Holds the cumulative sum of yDifferences
     *
     * @var float[]
     */
    private $cumulativeSum = [];

    /**
     * @var float
     */
    private $slope;

    /**
     * @var float
     */
    private $intercept;

    /**
     * @var float
     */
    private $rSquared;

    /**
     * @var int
     */
    private $coordinateCount = 0;

    /**
     * regression line points
     *
     * @var Point[]
     */
    private $xy = [];

    /**
     * LinearRegression constructor.
     */
    function __construct()
    {
    }

    /**
     * Append the data and compute the linear regression
     * multiple calls to train in a row keep adding data and calculate the new regression
     *
     *
     * @param array $xCoords the targets (e.g. degree days)
     * @param array $yCoords the samples (e.g. energy used for heating)
     */
    public function train(array $xCoords, array $yCoords)
    {
        $this->resetCalculatedValues();
        $this->appendData($xCoords, $yCoords);
        $this->compute();
    }

    /**
     * @param array $xCoords
     * @param array $yCoords
     */
    private function appendData(array $xCoords, array $yCoords)
    {
        $this->xCoords = array_merge($this->xCoords, $xCoords);
        $this->yCoords = array_merge($this->yCoords, $yCoords);
        $this->countCoordinates();
    }

    /**
     * clear the calculated values
     */
    private function resetCalculatedValues()
    {
        $this->slope         = null;
        $this->intercept     = null;
        $this->rSquared      = null;
        $this->yDifferences  = [];
        $this->cumulativeSum = [];
        $this->xy            = [];
    }

    /**
     * clear the series data
     */
    private function clearData()
    {
        $this->xCoords         = [];
        $this->yCoords         = [];
        $this->coordinateCount = 0;
    }

    /**
     * clear all data so new calls to train() start a fresh
     */
    public function reset()
    {
        $this->resetCalculatedValues();
        $this->clearData();
    }

    /**
     * The amount of increase in y (vertical) for an increase of 1 on the x axis (horizontal)
     *
     * @return float
     */
    public function getSlope(): float
    {
        return $this->returnOrThrowIfNull($this->slope);
    }

    /**
     * The value at which the regression line crosses the y axis (vertical)
     *
     * @return float
     */
    public function getIntercept(): float
    {
        return $this->returnOrThrowIfNull($this->intercept);
    }

    /**
     * The "coefficient of determination" or "r-squared value"
     * always a number between 0 and 1
     * 1, all of the data points fall perfectly on the regression line. The predictor x accounts for all of the
     * variation in y
     * 0, the estimated regression line is perfectly horizontal. The predictor x accounts for none of the variation in
     * y
     *
     * @return float
     */
    public function getRSquared()
    {
        return $this->returnOrThrowIfNull($this->rSquared);
    }

    /**
     * @return int
     * @throws SeriesCountMismatch
     * @throws SeriesHasZeroElements
     */
    private function countCoordinates(): int
    {
        // calculate number points
        $this->coordinateCount = count($this->xCoords);
        $yCount                = count($this->yCoords);

        // ensure both arrays of points are the same size
        if ($this->coordinateCount != $yCount) {
            throw new SeriesCountMismatch("Number of elements in arrays do not match {$this->xCoords}:{$yCount}");
        }
        if ($this->coordinateCount === 0) {
            throw new SeriesHasZeroElements('Series has zero elements');
        }
        return $this->coordinateCount;
    }

    /**
     * @param float $value
     *
     * @return float
     * @throws ParameterNotComputedYet
     */
    private function returnOrThrowIfNull(float $value)
    {
        if (null === $value) {
            throw new ParameterNotComputedYet('Parameter not compute yet');
        }
        return $value;
    }

    /**
     * Linear model that uses least squares method to approximate solution.
     */
    private function compute()
    {

        // calculate sums
        $x_sum = array_sum($this->xCoords);
        $y_sum = array_sum($this->yCoords);

        $xx_sum = 0;
        $xy_sum = 0;
        $yy_sum = 0;

        for ($i = 0; $i < $this->coordinateCount; $i++) {
            $xy_sum += ($this->xCoords[$i] * $this->yCoords[$i]);
            $xx_sum += ($this->xCoords[$i] * $this->xCoords[$i]);
            $yy_sum += ($this->yCoords[$i] * $this->yCoords[$i]);
        }

        // calculate slope
        $this->slope = (($this->coordinateCount * $xy_sum) - ($x_sum * $y_sum)) / (($this->coordinateCount * $xx_sum) - ($x_sum * $x_sum));

        // calculate intercept
        $this->intercept = ($y_sum - ($this->slope * $x_sum)) / $this->coordinateCount;

        // Calculate R squared
        // Math.pow((n*sum_xy - sum_x*sum_y)/Math.sqrt((n*sum_xx-sum_x*sum_x)*(n*sum_yy-sum_y*sum_y)),2);
        $this->rSquared = POW(($this->coordinateCount * $xy_sum - $x_sum * $y_sum) / sqrt(($this->coordinateCount * $xx_sum - $x_sum * $x_sum) * ($this->coordinateCount * $yy_sum - $y_sum * $y_sum)),
            2);

    }

    /**
     * predict for a given y value (sample) the x value (target)
     *
     * @param float $y
     *
     * @return float
     */
    public function predictX(float $y): float
    {
        return ($y - $this->getIntercept()) / $this->getSlope();
    }

    /**
     * predict for a given x value (target) the y value (sample)
     *
     * @param float $x
     *
     * @return float
     */
    public function predictY(float $x): float
    {
        return $this->getIntercept() + ($x * $this->getSlope());
    }

    /**
     * Get the differences of the actual data from the regression line
     * This is the differences in y values
     *
     * @return \float[]
     */
    public function getDifferencesFromRegressionLine()
    {
        if (0 === count($this->yDifferences)) {
            for ($i = 0; $i < $this->coordinateCount; $i++) {
                $this->yDifferences[] = $this->yCoords[$i] - $this->predictY($this->xCoords[$i]);
            }
        }
        return $this->yDifferences;
    }

    /**
     * Get the cumulative some of the differences from the regression line
     *
     * @return float[]
     */
    public function getCumulativeSumOfDifferencesFromRegressionLine()
    {
        if (0 === count($this->cumulativeSum)) {
            $differences         = $this->getDifferencesFromRegressionLine();
            $this->cumulativeSum = [$differences[0]];
            for ($i = 1; $i < $this->coordinateCount; $i++) {
                $this->cumulativeSum[$i] = $differences[$i] + $this->cumulativeSum[$i - 1];
            }
        }
        return $this->cumulativeSum;
    }

    /**
     * Mean of Y values
     *
     * @return float|int
     */
    public function getMeanY()
    {
        return array_sum($this->yCoords) / $this->coordinateCount;
    }

    /**
     * return an array of Points corresponding to the regression line of the current data
     *
     * @return Point[]
     */
    public function getRegressionLinePoints()
    {
        if (0 == count($this->xy)) {
            $minX      = min($this->xCoords);
            $maxX      = max($this->xCoords);
            $xStepSize = (($maxX - $minX) / ($this->coordinateCount - 1));
            $this->xy  = [];
            for ($i = 0; $i < $this->coordinateCount; $i++) {
                $x          = $minX + ($i * $xStepSize);
                $y          = $this->predictY($x);
                $this->xy[] = new Point($x, $y); // add point
            }
        }
        return $this->xy;
    }
}

class Point
{
    /**
     * @var float
     */
    private $x;

    /**
     * @var float
     */
    private $y;

    /**
     * Point constructor.
     *
     * @param float $x
     * @param float $y
     */
    function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @param float $x
     */
    public function setX(float $x)
    {
        $this->x = $x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @param float $y
     */
    public function setY(float $y)
    {
        $this->y = $y;
    }


}

class SeriesCountMismatch extends \Exception
{

    /**
     * SeriesCountMismatch constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @internal param string $string
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Class SeriesHasZeroElements
 *
 * @package MachineLearning\Regression
 */
class SeriesHasZeroElements extends \Exception
{

    /**
     * SeriesHasZeroElements constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
use Throwable;

class ParameterNotComputedYet extends \Exception
{

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}