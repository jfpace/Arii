<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StepStatus
 *
 * @ORM\Table(name="FOCUS_STEP_STATUS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\StepStatusRepository")
 */
class StepStatus
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
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetimetz")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetimetz",nullable=true)
     */
    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="error", type="integer", nullable=true)
     */
    private $error;
    
    /**
     * @var string
     *
     * @ORM\Column(name="error_text", type="string", length=255, nullable=true)
     */
    private $error_text;

    /**
     * @var string
     *
     * @ORM\Column(name="cause", type="string", length=50, nullable=true)
     */
    private $cause;

    /**
     * @var integer
     *
     * @ORM\Column(name="exit_code", type="integer", nullable=true)
     */
    private $exitCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", type="integer", nullable=true)
     */
    private $pid;

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
     * @return JobStatus
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
     * Set jobName
     *
     * @param string $jobName
     * @return JobStatus
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
     * Set spooler
     *
     * @param string $spooler
     * @return JobStatus
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
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return JobStatus
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return JobStatus
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     * @return JobStatus
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }
}
