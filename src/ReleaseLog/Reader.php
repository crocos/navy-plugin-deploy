<?php
namespace Crocos\Navy\DeployPlugin\ReleaseLog;

class Reader
{
    protected $period;
    protected $logfile;

    public function __construct(Logfile $logfile, Period $period)
    {
        $this->logfile = $logfile;
        $this->period = $period;
    }

    public function getRecentFilter($filter)
    {
        $period = $this->period->getRecentPeriod();
        $data = $this->loadData($period);

        $res = "## Period: $period Filter '$filter'" . PHP_EOL;
        list($filtered, $noFiltered) = $this->sliceDataByNeedle($data, $filter);

        if (empty($filtered)) {
            $log = ' empty';
        } else {
            $log = implode(PHP_EOL, $filtered);
        }

        return $res . $log;
    }

    public function getRecentGeneral()
    {
        $period = $this->period->getRecentPeriod();
        $data = $this->loadData($period);

        list($dev, $noDev) = $this->sliceDataByNeedle($data, 'dev');
        $result = $this->fetchPopularFilter($noDev, $period);

        return "## Period: $period Release General List" . PHP_EOL . $result;
    }

    public function getRecentDev()
    {
        $period = $this->period->getRecentPeriod();
        $data = $this->loadData($period);

        list($dev, $noDev) = $this->sliceDataByNeedle($data, 'dev');
        $result = $this->fetchPopularFilter($dev, $period);

        return "## Period: $period Release Dev List" . PHP_EOL . $result;
    }

    protected function fetchPopularFilter($data, $period)
    {
        list($topic, $noTopic)       = $this->sliceDataByNeedle($data, 'topic');
        list($internal, $noInternal) = $this->sliceDataByNeedle($noTopic, 'internal');
        list($custom, $noCustom)     = $this->sliceDataByNeedle($noInternal, 'custom');
        list($bugfix, $noBugfix)     = $this->sliceDataByNeedle($noCustom, 'bugfix');
        $other = $noBugfix;

        $tmp = PHP_EOL;
        $tmp .= "### リリーストピック" . PHP_EOL;
        $tmp .= $this->join($topic) . PHP_EOL;
        $tmp .= PHP_EOL;
        $tmp .= "### バグ修正" . PHP_EOL;
        $tmp .= $this->join($bugfix) . PHP_EOL;
        $tmp .= PHP_EOL;
        $tmp .= "### カスタマイズ" . PHP_EOL;
        $tmp .= $this->join($custom) . PHP_EOL;
        $tmp .= PHP_EOL;
        $tmp .= "### internal" . PHP_EOL;
        $tmp .= $this->join($internal) . PHP_EOL;
        $tmp .= PHP_EOL;
        $tmp .= "その他リリース" . PHP_EOL;
        $tmp .= '---------------------------------------' . PHP_EOL;
        $tmp .= PHP_EOL;
        $tmp .= $this->join($other);

        return $tmp;
    }

    /**
     * @return array($result, $surplus)
     */
    protected function sliceDataByNeedle($data, $needle)
    {
         $result = preg_grep('/.*\['.preg_quote($needle, '/').'\].*/i', $data);
         $surplus = preg_grep('/.*\['.preg_quote($needle, '/').'\].*/i', $data, true);

         return [ $result, $surplus ];
    }

    protected function loadData($period)
    {
        $file = $this->logfile->read($period);
        if (substr($file, 0, 1) === '*') {
            $file = substr($file, 1);
        }

        return explode(PHP_EOL . '*', $file);
    }

    protected function join(array $data = [])
    {
        if (empty($data)) {
            return '';
        }

        return '*' . implode(PHP_EOL . '*', $data);
    }
}
