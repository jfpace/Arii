<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * lock_use
 *
 * @ORM\Table(name="FOCUS_LOCKS_USE")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\LocksUseRepository")
 */
class LocksUse
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Jobs")
     * @ORM\JoinColumn(name="job_id",referencedColumnName="id")
     * 
     **/
    private $job;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Spoolers")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $spooler;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Locks")
     * @ORM\JoinColumn(name="lock_id",referencedColumnName="id",nullable=true)
     * 
     */
    private $lock;

    /**
     * @var integer
     *
     * @ORM\Column(name="exclusive", type="boolean")
     */
    private $exclusive;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_available", type="boolean")
     */
    private $is_available;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_missing", type="boolean")
     */
    private $is_missing;

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
     * @return lock_use
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
     * @return lock_use
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
     * @return lock_use
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
     * Set job
     *
     * @param \Arii\FocusBundle\Entity\Jobs $job
     * @return lock_use
     */
    public function setJob(\Arii\FocusBundle\Entity\Jobs $job)
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
     * Set exclusive
     *
     * @param integer $exclusive
     * @return lock_use
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
    
        return $this;
    }

    /**
     * Get exclusive
     *
     * @return integer 
     */
    public function getExclusive()
    {
        return $this->exclusive;
    }

    /**
     * Set lock
     *
     * @param \Arii\FocusBundle\Entity\Locks $lock
     * @return lock_use
     */
    public function setLock(\Arii\FocusBundle\Entity\Locks $lock)
    {
        $this->lock = $lock;
    
        return $this;
    }

    /**
     * Get lock
     *
     * @return string 
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return LockUse
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
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add job
     *
     * @param \Arii\FocusBundle\Entity\Jobs $job
     * @return LockUse
     */
    public function addJob(\Arii\FocusBundle\Entity\Jobs $job)
    {
        $this->job[] = $job;
    
        return $this;
    }

    /**
     * Remove job
     *
     * @param \Arii\FocusBundle\Entity\Jobs $job
     */
    public function removeJob(\Arii\FocusBundle\Entity\Jobs $job)
    {
        $this->job->removeElement($job);
    }

    /**
     * Set is_missing
     *
     * @param boolean $isMissing
     * @return LocksUse
     */
    public function setIsMissing($isMissing)
    {
        $this->is_missing = $isMissing;
    
        return $this;
    }

    /**
     * Get is_missing
     *
     * @return boolean 
     */
    public function getIsMissing()
    {
        return $this->is_missing;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return LocksUse
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
     * Get jobs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getJobs()
    {
        return $this->jobs;
    }
}