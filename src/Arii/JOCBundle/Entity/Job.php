<?php

namespace Arii\JOCBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Arii\JOCBundle\Entity\JobRepository")
 */
class Job
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
     * @var string
     *
     * @ORM\Column(name="spooler_id", type="string", length=255)
     */
    private $spooler_id;

    /**
     * @var string
     *
     * @ORM\Column(name="job_name", type="string", length=255)
     */
    private $job_name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=12)
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_steps", type="smallint")
     */
    private $all_steps;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_tasks", type="smallint")
     */
    private $all_tasks;

    /**
     * @var boolean
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
     * @var boolean
     *
     * @ORM\Column(name="in_period", type="boolean")
     */
    private $in_period;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_write_time", type="datetime")
     */
    private $last_write_time;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_warning", type="datetime")
     */
    private $last_warning;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_start_time", type="datetime")
     */
    private $next_start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="process_class", type="string", length=255)
     */
    private $process_class;


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
     * @return Job
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
     * Set job_name
     *
     * @param string $jobName
     * @return Job
     */
    public function setJobName($jobName)
    {
        $this->job_name = $jobName;
    
        return $this;
    }

    /**
     * Get job_name
     *
     * @return string 
     */
    public function getJobName()
    {
        return $this->job_name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Job
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
     * @return Job
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
     * @return Job
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
     * @return Job
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
     * @param boolean $ordered
     * @return Job
     */
    public function setOrdered($ordered)
    {
        $this->ordered = $ordered;
    
        return $this;
    }

    /**
     * Get ordered
     *
     * @return boolean 
     */
    public function getOrdered()
    {
        return $this->ordered;
    }

    /**
     * Set has_description
     *
     * @param boolean $hasDescription
     * @return Job
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
     * @return Job
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
     * @param boolean $inPeriod
     * @return Job
     */
    public function setInPeriod($inPeriod)
    {
        $this->in_period = $inPeriod;
    
        return $this;
    }

    /**
     * Get in_period
     *
     * @return boolean 
     */
    public function getInPeriod()
    {
        return $this->in_period;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return Job
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    
        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return Job
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
     * Set last_warning
     *
     * @param \DateTime $lastWarning
     * @return Job
     */
    public function setLastWarning($lastWarning)
    {
        $this->last_warning = $lastWarning;
    
        return $this;
    }

    /**
     * Get last_warning
     *
     * @return \DateTime 
     */
    public function getLastWarning()
    {
        return $this->last_warning;
    }

    /**
     * Set next_start_time
     *
     * @param \DateTime $nextStartTime
     * @return Job
     */
    public function setNextStartTime($nextStartTime)
    {
        $this->next_start_time = $nextStartTime;
    
        return $this;
    }

    /**
     * Get next_start_time
     *
     * @return \DateTime 
     */
    public function getNextStartTime()
    {
        return $this->next_start_time;
    }

    /**
     * Set process_class
     *
     * @param string $processClass
     * @return Job
     */
    public function setProcessClass($processClass)
    {
        $this->process_class = $processClass;
    
        return $this;
    }

    /**
     * Get process_class
     *
     * @return string 
     */
    public function getProcessClass()
    {
        return $this->process_class;
    }
}