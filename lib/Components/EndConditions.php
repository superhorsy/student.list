<?php


namespace App\Components;


class EndConditions
{

    public static function checkIfShouldContinueTournament(array $region2count): bool
    {
        return !(static::endCondition1Happend($region2count));
    }

    /**
     * Когда остается один регион с кол-вом участников меньше 10,
     * а сумма участников в других регионах не превышает 5
     *
     * @param array $region2count
     * @return bool
     */
    private static function endCondition1Happend(array $region2count)
    {
        $countOthers = 0;

        $max = max($region2count);
        if ($max > 9) return false;

        $keyMax = array_keys($region2count, $max);

        foreach ($region2count as $region => $count) {
            if ($region === $keyMax[0]) continue;
            $countOthers += $count;
        }

        if ($countOthers > 5) return false;

        return true;
    }

    /**
     * Когда нет регионов с количеством участников больше 5
     * и сумма всех оставшихся меньше 10
     *
     * @return bool
     */
    private static function endCondition2Happend(array $region2count)
    {
        if (max($region2count) >= 5 || array_sum($region2count) <= 10) return false;

        return true;
    }
}