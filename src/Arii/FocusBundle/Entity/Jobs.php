<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_jobs
 *
 * @ORM\Table(name="FOCUS_JOBS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\JobsRepository")
 */
class Jobs
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
     * @var string
     *
     * @ORM\Column(name="spooler_name", type="string", length=64)
     */
    private $spooler_name;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=512)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="state_text", type="string", length=100, nullable=true)
     */
    private $state_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_steps", type="smallint")
     */
    private $all_steps;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_chain_priority", type="smallint")
     */
    private $job_chain_priority;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_tasks", type="smallint")
     */
    private $all_tasks;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordered", type="boolean")
     */
    private $ordered;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_description", type="boolean")
     */
    private $has_description;

    /**
     * @var integer
     *
     * @ORM\Column(name="tasks", type="smallint")
     */
    private $tasks;

    /**
     * @var integer
     *
     * @ORM\Column(name="in_period", type="boolean")
     */
    private $in_period;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_write_time", type="datetime", nullable=true)
     */
    private $last_write_time;

    /**
     * @var string
     *
     * @ORM\Column(name="last_info", type="string", length=255, nullable=true)
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
     * @ORM\Column(name="error", type="text", length=1024, nullable=true)
     */
    private $error;

    /**
     * @var string
     *
     * @ORM\Column(name="next_start_time", type="datetime", nullable=true)
     */
    private $next_start_time;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\ProcessClasses")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $process_class;

    /**
     * @var string
     *
     * @ORM\Column(name="process_class_name", type="string",  length=255, nullable=true)
     */
    private $process_class_name;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Schedules")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $schedule;

    /**
     * @var string
     *
     * @ORM\Column(name="schedule_name", type="string",  length=255, nullable=true)
     */
    private $schedule_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="waiting_for_process", type="boolean")
     */
    private $waiting_for_process;

    /**
     * @var string
     *
     * @ORM\Column(name="highest_level", type="string", length=10, nullable=true)
     */
    private $highest_level;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=10, nullable=true)
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="error_code", type="string", length=20, nullable=true)
     */
    private $error_code;

    /**
     * @var string
     *
     * @ORM\Column(name="error_text", type="string", length=255, nullable=true)
     */
    private $error_text;

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
     * Set job
     *
     * @param string $job
     * @return state_jobs
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
     * Set title
     *
     * @param string $title
     * @return state_jobs
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return state_jobs
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
     * Set all_steps
     *
     * @param integer $allSteps
     * @return state_jobs
     */
    public function setAllSteps($allSteps)
    {
        $this->all_steps = $allSteps;
    
        return $this;
    }

    /**
     * Get all_steps
     *
     * @return integer 
     */
    public function getAllSteps()
    {
        return $this->all_steps;
    }

    /**
     * Set all_tasks
     *
     * @param integer $allTasks
     * @return state_jobs
     */
    public function setAllTasks($allTasks)
    {
        $this->all_tasks = $allTasks;
    
        return $this;
    }

    /**
     * Get all_tasks
     *
     * @return integer 
     */
    public function getAllTasks()
    {
        return $this->all_tasks;
    }

    /**
     * Set ordered
     *
     * @param integer $ordered
     * @return state_jobs
     */
    public function setOrdered($ordered)
    {
        $this->ordered = $ordered;
    
        return $this;
    }

    /**
     * Get ordered
     *
     * @return integer 
     */
    public function getOrdered()
    {
        return $this->ordered;
    }

    /**
     * Set has_description
     *
     * @param boolean $hasDescription
     * @return state_jobs
     */
    public function setHasDescription($hasDescription)
    {
        $this->has_description = $hasDescription;
    
        return $this;
    }

    /**
     * Get has_description
     *
     * @return boolean 
     */
    public function getHasDescription()
    {
        return $this->has_description;
    }

    /**
     * Set tasks
     *
     * @param integer $tasks
     * @return state_jobs
     */
    public function setTasks($tasks)
    {
        $this->tasks = $tasks;
    
        return $this;
    }

    /**
     * Get tasks
     *
     * @return integer 
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Set in_period
     *
     * @param integer $inPeriod
     * @return state_jobs
     */
    public function setInPeriod($inPeriod)
    {
        $this->in_period = $inPeriod;
    
        return $this;
    }

    /**
     * Get in_period
     *
     * @return integer 
     */
    public function getInPeriod()
    {
        return $this->in_period;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return state_jobs
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    
        return $this;
    }

    /**
     * Get enabled
     *
     * @return integer 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return state_jobs
     */
    public function setLastWriteTime($lastWriteTime)
    {
        $this->last_write_time = $lastWriteTime;
    
        return $this;
    }

    /**
     * Get last_write_time
     *
     * @return \DateTime 
     */
    public function getLastWriteTime()
    {
        return $this->last_write_time;
    }

    /**
     * Set last_info
     *
     * @param string $lastInfo
     * @return state_jobs
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
     * Set last_warning
     *
     * @param string $lastWarning
     * @return state_jobs
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
     * Set next_start_time
     *
     * @param string $nextStartTime
     * @return state_jobs
     */
    public function setNextStartTime($nextStartTime)
    {
        $this->next_start_time = $nextStartTime;
    
        return $this;
    }

    /**
     * Get next_start_time
     *
     * @return string 
     */
    public function getNextStartTime()
    {
        return $this->next_start_time;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return Jobs
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
     * Set path
     *
     * @param string $path
     * @return Jobs
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set process_class
     *
     * @param \Arii\FocusBundle\Entity\ProcessClasses $processClass
     * @return Jobs
     */
    public function setProcessClass(\Arii\FocusBundle\Entity\ProcessClasses $processClass = null)
    {
        $this->process_class = $processClass;
    
        return $this;
    }

    /**
     * Get process_class
     *
     * @return \Arii\FocusBundle\Entity\ProcessClasses 
     */
    public function getProcessClass()
    {
        return $this->process_class;
    }

    /**
     * Set waiting_for_process
     *
     * @param boolean $waitingForProcess
     * @return Jobs
     */
    public function setWaitingForProcess($waitingForProcess)
    {
        $this->waiting_for_process = $waitingForProcess;
    
        return $this;
    }

    /**
     * Get waiting_for_process
     *
     * @return boolean 
     */
    public function getWaitingForProcess()
    {
        return $this->waiting_for_process;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Jobs
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
     * @return Jobs
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
     * Set level
     *
     * @param string $level
     * @return Jobs
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
     * Set last_error
     *
     * @param string $lastError
     * @return Jobs
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

    /**
     * Set error_code
     *
     * @param string $errorCode
     * @return Jobs
     */
    public function setErrorCode($errorCode)
    {
        $this->error_code = $errorCode;
    
        return $this;
    }

    /**
     * Get error_code
     *
     * @return string 
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * Set error_text
     *
     * @param string $errorText
     * @return Jobs
     */
    public function setErrorText($errorText)
    {
        $this->error_text = $errorText;
    
        return $this;
    }

    /**
     * Get error_text
     *
     * @return string 
     */
    public function getErrorText()
    {
        return $this->error_text;
    }

    /**
     * Set state_text
     *
     * @param string $stateText
     * @return Jobs
     */
    public function setStateText($stateText)
    {
        $this->state_text = $stateText;
    
        return $this;
    }

    /**
     * Get state_text
     *
     * @return string 
     */
    public function getStateText()
    {
        return $this->state_text;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Jobs
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
}