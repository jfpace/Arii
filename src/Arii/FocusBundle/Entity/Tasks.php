<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_tasks
 *
 * @ORM\Table(name="FOCUS_TASKS")
* @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\TasksRepository")
 */
class Tasks
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Spoolers")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $spooler;
   
    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Jobs")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $job;

    /**
     * @var string
     *
     * @ORM\Column(name="task", type="integer")
     */
    private $task;

    /**
     * @var string
     *
     * @ORM\Column(name="run", type="integer", nullable=true)
     */
    private $run;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=25)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="running_since", type="datetime")
     */
    private $running_since;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="enqueued", type="datetime", nullable=true)
     */
    private $enqueued;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime")
     */
    private $start_at;

    /**
     * @var string
     *
     * @ORM\Column(name="cause", type="string", length=32)
     */
    private $cause;

    /**
     * @var integer
     *
     * @ORM\Column(name="steps", type="integer")
     */
    private $steps;

    /**
     * @var string
     *
     * @ORM\Column(name="logfile", type="string", length=255, nullable=true)
     */
    private $logfile;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", type="integer")
     */
    private $pid;

    /**
     * @var string
     *
     * @ORM\Column(name="priority", type="string", length=12)
     */
    private $priority;

    /**
     * @var integer
     *
     * @ORM\Column(name="force_start", type="boolean", nullable=true)
     */
    private $force_start;
    
    /**
     * @var string
     *
     * @ORM\Column(name="highest_level", type="string", length=10, nullable=true)
     */
    private $highest_level;

    /**
     * @var string
     *
     * @ORM\Column(name="last_info", type="text", length=1024, nullable=true)
     */
    private $last_info;

    /**
     * @var string
     *
     * @ORM\Column(name="last_warning", type="string", length=255, nullable=true)
     */
    private $last_warning;

    /**
     * @var string
     *
     * @ORM\Column(name="last_error", type="text", length=1024, nullable=true)
     */
    private $last_error;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=10, nullable=true)
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="crc", type="string", length=9, nullable=true)
     */
    private $crc;

    /**
     * @var integer $updated
     *
     * @ORM\Column(name="updated", type="integer")
     */
    private $updated;

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
     * Set spooler_id
     *
     * @param string $spoolerId
     * @return state_tasks
     */
    public function setSpoolerId($spoolerId)
    {
        $this->spooler_id = $spoolerId;
    
        return $this;
    }

    /**
     * Get spooler_id
     *
     * @return string 
     */
    public function getSpoolerId()
    {
        return $this->spooler_id;
    }

    /**
     * Set job
     *
     * @param string $job
     * @return state_tasks
     */
    public function setJob($job)
    {
        $this->job = $job;
    
        return $this;
    }

    /**
     * Get job
     *
     * @return string 
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set task_id
     *
     * @param integer $taskId
     * @return state_tasks
     */
    public function setTaskId($taskId)
    {
        $this->task_id = $taskId;
    
        return $this;
    }

    /**
     * Get task_id
     *
     * @return integer 
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * Set task
     *
     * @param string $task
     * @return state_tasks
     */
    public function setTask($task)
    {
        $this->task = $task;
    
        return $this;
    }

    /**
     * Get task
     *
     * @return string 
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return state_tasks
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return state_tasks
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set running_since
     *
     * @param \DateTime $runningSince
     * @return state_tasks
     */
    public function setRunningSince($runningSince)
    {
        $this->running_since = $runningSince;
    
        return $this;
    }

    /**
     * Get running_since
     *
     * @return \DateTime 
     */
    public function getRunningSince()
    {
        return $this->running_since;
    }

    /**
     * Set enqueued
     *
     * @param \DateTime $enqueued
     * @return state_tasks
     */
    public function setEnqueued($enqueued)
    {
        $this->enqueued = $enqueued;
    
        return $this;
    }

    /**
     * Get enqueued
     *
     * @return \DateTime 
     */
    public function getEnqueued()
    {
        return $this->enqueued;
    }

    /**
     * Set start_at
     *
     * @param \DateTime $startAt
     * @return state_tasks
     */
    public function setStartAt($startAt)
    {
        $this->start_at = $startAt;
    
        return $this;
    }

    /**
     * Get start_at
     *
     * @return \DateTime 
     */
    public function getStartAt()
    {
        return $this->start_at;
    }

    /**
     * Set cause
     *
     * @param string $cause
     * @return state_tasks
     */
    public function setCause($cause)
    {
        $this->cause = $cause;
    
        return $this;
    }

    /**
     * Get cause
     *
     * @return string 
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * Set steps
     *
     * @param integer $steps
     * @return state_tasks
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
    
        return $this;
    }

    /**
     * Get steps
     *
     * @return integer 
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Set logfile
     *
     * @param string $logfile
     * @return state_tasks
     */
    public function setLogfile($logfile)
    {
        $this->logfile = $logfile;
    
        return $this;
    }

    /**
     * Get logfile
     *
     * @return string 
     */
    public function getLogfile()
    {
        return $this->logfile;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     * @return state_tasks
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

    /**
     * Set priority
     *
     * @param string $priority
     * @return state_tasks
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    
        return $this;
    }

    /**
     * Get priority
     *
     * @return string 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set force_start
     *
     * @param integer $forceStart
     * @return state_tasks
     */
    public function setForceStart($forceStart)
    {
        $this->force_start = $forceStart;
    
        return $this;
    }

    /**
     * Get force_start
     *
     * @return integer 
     */
    public function getForceStart()
    {
        return $this->force_start;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return Tasks
     */
    public function setSpooler(\Arii\FocusBundle\Entity\Spoolers $spooler = null)
    {
        $this->spooler = $spooler;
    
        return $this;
    }

    /**
     * Get spooler
     *
     * @return \Arii\FocusBundle\Entity\Spoolers 
     */
    public function getSpooler()
    {
        return $this->spooler;
    }

    /**
     * Set run
     *
     * @param integer $run
     * @return Tasks
     */
    public function setRun($run)
    {
        $this->run = $run;
    
        return $this;
    }

    /**
     * Get run
     *
     * @return integer 
     */
    public function getRun()
    {
        return $this->run;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Tasks
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set highest_level
     *
     * @param string $highestLevel
     * @return Tasks
     */
    public function setHighestLevel($highestLevel)
    {
        $this->highest_level = $highestLevel;
    
        return $this;
    }

    /**
     * Get highest_level
     *
     * @return string 
     */
    public function getHighestLevel()
    {
        return $this->highest_level;
    }

    /**
     * Set last_info
     *
     * @param string $lastInfo
     * @return Tasks
     */
    public function setLastInfo($lastInfo)
    {
        $this->last_info = $lastInfo;
    
        return $this;
    }

    /**
     * Get last_info
     *
     * @return string 
     */
    public function getLastInfo()
    {
        return $this->last_info;
    }

    /**
     * Set level
     *
     * @param string $level
     * @return Tasks
     */
    public function setLevel($level)
    {
        $this->level = $level;
    
        return $this;
    }

    /**
     * Get level
     *
     * @return string 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set last_warning
     *
     * @param string $lastWarning
     * @return Tasks
     */
    public function setLastWarning($lastWarning)
    {
        $this->last_warning = $lastWarning;
    
        return $this;
    }

    /**
     * Get last_warning
     *
     * @return string 
     */
    public function getLastWarning()
    {
        return $this->last_warning;
    }

    /**
     * Set last_error
     *
     * @param string $lastError
     * @return Tasks
     */
    public function setLastError($lastError)
    {
        $this->last_error = $lastError;
    
        return $this;
    }

    /**
     * Get last_error
     *
     * @return string 
     */
    public function getLastError()
    {
        return $this->last_error;
    }
}