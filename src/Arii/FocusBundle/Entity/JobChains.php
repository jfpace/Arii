<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_job_chains
 *
 * @ORM\Table(name="FOCUS_JOB_CHAINS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\JobChainsRepository")
 */
class JobChains
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
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    private $state;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id_space", type="string", length=32, nullable=true)
     */
    private $order_id_space;

    /**
     * @var integer
     *
     * @ORM\Column(name="orders_recoverable", type="boolean", nullable=true)
     */
    private $orders_recoverable;

    /**
     * @var smallint
     *
     * @ORM\Column(name="running_orders", type="smallint", nullable=true)
     */
    private $running_orders;

    /**
     * @var smallint
     *
     * @ORM\Column(name="max_orders", type="smallint", nullable=true)
     */
    private $max_orders;

    /**
     * @var smallint
     *
     * @ORM\Column(name="orders", type="smallint", nullable=true)
     */
    private $orders;

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
     * @return state_job_chain
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
     * @return state_job_chain
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
     * Set file
     *
     * @param string $file
     * @return state_job_chain
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
     * Set job_chain
     *
     * @param string $jobChain
     * @return state_job_chain
     */
    public function setJobChain($jobChain)
    {
        $this->job_chain = $jobChain;
    
        return $this;
    }

    /**
     * Get job_chain
     *
     * @return string 
     */
    public function getJobChain()
    {
        return $this->job_chain;
    }

    /**
     * Set order_recoverable
     *
     * @param integer $orderRecoverable
     * @return state_job_chain
     */
    public function setOrderRecoverable($orderRecoverable)
    {
        $this->order_recoverable = $orderRecoverable;
    
        return $this;
    }

    /**
     * Get order_recoverable
     *
     * @return integer 
     */
    public function getOrderRecoverable()
    {
        return $this->order_recoverable;
    }

    /**
     * Set orders
     *
     * @param integer $orders
     * @return state_job_chain
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;
    
        return $this;
    }

    /**
     * Get orders
     *
     * @return integer 
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return state_job_chain
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
     * Set state
     *
     * @param string $state
     * @return state_job_chains
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
     * Set title
     *
     * @param string $title
     * @return state_job_chains
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
     * Set running_orders
     *
     * @param integer $runningOrders
     * @return state_job_chains
     */
    public function setRunningOrders($runningOrders)
    {
        $this->running_orders = $runningOrders;
    
        return $this;
    }

    /**
     * Get running_orders
     *
     * @return integer 
     */
    public function getRunningOrders()
    {
        return $this->running_orders;
    }

    /**
     * Get job_chain_id
     *
     * @return integer 
     */
    public function getJobChainId()
    {
        return $this->job_chain_id;
    }

    /**
     * Set orders_recoverable
     *
     * @param boolean $ordersRecoverable
     * @return state_job_chains
     */
    public function setOrdersRecoverable($ordersRecoverable)
    {
        $this->orders_recoverable = $ordersRecoverable;
    
        return $this;
    }

    /**
     * Get orders_recoverable
     *
     * @return boolean 
     */
    public function getOrdersRecoverable()
    {
        return $this->orders_recoverable;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return JobChains
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
     * Set order
     *
     * @param \Arii\FocusBundle\Entity\Orders $order
     * @return JobChains
     */
    public function setOrder(\Arii\FocusBundle\Entity\Orders $order = null)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return \Arii\FocusBundle\Entity\Orders 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return JobChains
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return JobChains
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
     * Set max_orders
     *
     * @param integer $maxOrders
     * @return JobChains
     */
    public function setMaxOrders($maxOrders)
    {
        $this->max_orders = $maxOrders;
    
        return $this;
    }

    /**
     * Get max_orders
     *
     * @return integer 
     */
    public function getMaxOrders()
    {
        return $this->max_orders;
    }
}