<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * lock_use
 *
 * @ORM\Table(name="FOCUS_ORDER_ID_SPACES")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\OrderIdSpacesRepository")
 */
class OrderIdSpaces
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
     * @ORM\Column(name="name", type="string", length=32, nullable=true)
     **/
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\JobChains")
     * @ORM\JoinColumn(name="job_chain_id",referencedColumnName="id")
     * 
     **/
    private $job_chain;

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