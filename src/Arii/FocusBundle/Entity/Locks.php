<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * statelocks
 *
 * @ORM\Table(name="FOCUS_LOCKS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\LocksRepository")
 */
class Locks
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
     * @ORM\Column(name="max_non_exclusive", type="integer", nullable=true)
     */
    private $max_non_exclusive;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_free", type="boolean")
     */
    private $is_free;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100)
     */
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_write_time", type="datetime", nullable=true)
     */
    private $last_write_time;
    
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
     * @return statelocks
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
     * @return statelocks
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
     * @return statelocks
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
     * Set max_non_exclusive
     *
     * @param integer $maxNonExclusive
     * @return statelocks
     */
    public function setMaxNonExclusive($maxNonExclusive)
    {
        $this->max_non_exclusive = $maxNonExclusive;
    
        return $this;
    }

    /**
     * Get max_non_exclusive
     *
     * @return integer 
     */
    public function getMaxNonExclusive()
    {
        return $this->max_non_exclusive;
    }

    /**
     * Set is_free
     *
     * @param boolean $isFree
     * @return statelocks
     */
    public function setIsFree($isFree)
    {
        $this->is_free = $isFree;
    
        return $this;
    }

    /**
     * Get is_free
     *
     * @return boolean 
     */
    public function getIsFree()
    {
        return $this->is_free;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return statelocks
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
     * Set file
     *
     * @param string $file
     * @return statelocks
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
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return statelocks
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
     * Set lock
     *
     * @param string $lock
     * @return state_locks
     */
    public function setLock($lock)
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
     * @return Locks
     */
    public function setSpooler(\Arii\FocusBundle\Entity\Spoolers $spooler = null)
    {
        $this->spooler = $spooler;
    
        return $this;
    }

    /**
     * Get spooler
     *
     * @return \Arii\FocusBundle\Entity\Schedulers 
     */
    public function getSpooler()
    {
        return $this->spooler;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Locks
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