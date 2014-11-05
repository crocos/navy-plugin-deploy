<?php
namespace Crocos\Navy\DeployPlugin\ReleaseLog;

class Period
{
    protected $datetime;

    public function __construct(\DateTimeInterface $datetime = null)
    {
        $this->datetime = $datetime ?: new \DateTime();
    }
    public function getRecentPeriod()
    {
        return $this->datetime->modify('+1 Friday')->format('Ymd');
    }

    public function getNextPeriod()
    {
        $today = $this->datetime;
        if ($today->format('w') === '5') {
            $period = $today->modify('+1 day')->modify('+1 Friday')->format('Ymd');
        } else {
            $period = $this->getRecentPeriod();
        }

        return $period;
    }
}
