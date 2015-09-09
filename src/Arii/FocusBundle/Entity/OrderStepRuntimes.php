<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JobRuntimes
 *
 * @ORM\Table(name="FOCUS_ORDER_STEP_RUNTIMES")
 * @ORM\Entity
 */
class OrderStepRuntimes
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Arii\FocusBundle\Entity\Orders")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $order;

    /**
     * @ORM\OneToOne(targetEntity="Arii\FocusBundle\Entity\JobChainNodes")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $job_chain_node;

    /**
     * @var integer
     *
     * @ORM\Column(name="history_id", type="integer")
     */
    private $historyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_id", type="integer")
     */
    private $taskId;

    /**
     * @var float
     *
     * @ORM\Column(name="run_time", type="float")
     */
    private $runTime;

    /**
     * @var float
     *
     * @ORM\Column(name="runs", type="integer")
     */
    private $runs;
    
    /**
     * @var float
     *
     * @ORM\Column(name="diff", type="float")
     */
    private $diff;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer")
     */
    private $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="crc", type="string", length=9, nullable=true)
     */
    private $crc;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set historyId
     *
     * @param integer $historyId
     * @return JobRuntimes
     */
    public function setHistoryId($historyId)
    {
        $this->historyId = $historyId;

        return $this;
    }

    /**
     * Get historyId
     *
     * @return integer 
     */
    public function getHistoryId()
    {
        return $this->historyId;
    }

    /**
     * Set spooler
     *
     * @param string $spooler
     * @return JobRuntimes
     */
    public function setSpooler($spooler)
    {
        $this->spooler = $spooler;

        return $this;
    }

    /**
     * Get spooler
     *
     * @return string 
     */
    public function getSpooler()
    {
        return $this->spooler;
    }

    /**
     * Set jobName
     *
     * @param string $jobName
     * @return JobRuntimes
     */
    public function setJobName($jobName)
    {
        $this->jobName = $jobName;

        return $this;
    }

    /**
     * Get jobName
     *
     * @return string 
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * Set avgRuntime
     *
     * @param float $avgRuntime
     * @return JobRuntimes
     */
    public function setAvgRuntime($avgRuntime)
    {
        $this->avgRuntime = $avgRuntime;

        return $this;
    }

    /**
     * Get avgRuntime
     *
     * @return float 
     */
    public function getAvgRuntime()
    {
        return $this->avgRuntime;
    }
}
