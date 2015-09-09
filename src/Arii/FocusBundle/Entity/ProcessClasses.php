<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_process_classes
 *
 * @ORM\Table(name="FOCUS_PROCESS_CLASSES")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\ProcessClassesRepository")
 */
class ProcessClasses
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_processes", type="integer")
     */
    private $max_processes;

    /**
     * @var string
     *
     * @ORM\Column(name="remote_scheduler", type="string", length=255, nullable=true)
     */
    private $remote_scheduler;

    /**
     * @var integer
     *
     * @ORM\Column(name="processes", type="integer")
     */
    private $processes;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    private $state;
    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     */
    private $file;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_write_time", type="datetime", nullable=true)
     */
    private $last_write_time;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=128, nullable=true)
     */
    private $error;

    /**
     * @var string
     *
     * @ORM\Column(name="crc", type="string", length=9, nullable=true)
     */
    private $crc;

    /**
     * @var integer
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
     * @return state_process_classes
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
     * Set path
     *
     * @param string $path
     * @return state_process_classes
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
     * Set name
     *
     * @param string $name
     * @return state_process_classes
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
     * Set process_class
     *
     * @param string $processClass
     * @return state_process_classes
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

    /**
     * Set max_process
     *
     * @param integer $maxProcess
     * @return state_process_classes
     */
    public function setMaxProcess($maxProcess)
    {
        $this->max_process = $maxProcess;
    
        return $this;
    }

    /**
     * Get max_process
     *
     * @return integer 
     */
    public function getMaxProcess()
    {
        return $this->max_process;
    }

    /**
     * Set remote_scheduler
     *
     * @param string $remoteScheduler
     * @return state_process_classes
     */
    public function setRemoteScheduler($remoteScheduler)
    {
        $this->remote_scheduler = $remoteScheduler;
    
        return $this;
    }

    /**
     * Get remote_scheduler
     *
     * @return string 
     */
    public function getRemoteScheduler()
    {
        return $this->remote_scheduler;
    }

    /**
     * Set processes
     *
     * @param integer $processes
     * @return state_process_classes
     */
    public function setProcesses($processes)
    {
        $this->processes = $processes;
    
        return $this;
    }

    /**
     * Get processes
     *
     * @return integer 
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return state_process_classes
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
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return state_process_classes
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
     * Set max_processes
     *
     * @param integer $maxProcesses
     * @return state_process_classes
     */
    public function setMaxProcesses($maxProcesses)
    {
        $this->max_processes = $maxProcesses;
    
        return $this;
    }

    /**
     * Get max_processes
     *
     * @return integer 
     */
    public function getMaxProcesses()
    {
        return $this->max_processes;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return state_process_classes
     */
    public function setFile($file)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return ProcessClasses
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return ProcessClasses
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
}