<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_orders
 *
 * @ORM\Table(name="FOCUS_ORDERS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\OrdersRepository")
 */
class Orders
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
     * 
     * @ORM\ManyToOne(targetEntity="JobChainNodes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $job_chain_node;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\JobChains")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $job_chain;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100)
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=1024)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="state_text", type="string", length=1024)
     */
    private $state_text;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_start_time", type="datetime", nullable=true)
     */
    private $next_start_time;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="setback", type="datetime", nullable=true)
     */
    private $setback;

    /**
     * @var integer
     *
     * @ORM\Column(name="setback_count", type="integer", nullable=true)
     */
    private $setback_count;

    /**
     * @var string
     *
     * @ORM\Column(name="initial_state", type="string", length=100)
     */
    private $initial_state;
    /**
     * @var string
     *
     * @ORM\Column(name="end_state", type="string", length=100, nullable=true)
     */
    private $end_state;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer", nullable=true)
     */
    private $priority;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    private $start_time;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="suspended", type="boolean")
     */
    private $suspended;

    /**
     * @var integer
     *
     * @ORM\Column(name="history_id", type="integer", nullable=true)
     */
    private $history_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_id", type="integer", nullable=true)
     */
    private $task_id;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Schedules")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $schedule;

    /**
     * @var string
     *
     * @ORM\Column(name="in_process_since", type="time", nullable=true)
     */
    private $in_process_since;

    /**
     * @var string
     *
     * @ORM\Column(name="touched", type="boolean" )
     */
    private $touched;

    /**
     * @var string
     *
     * @ORM\Column(name="on_blacklist", type="boolean" )
     */
    private $on_blacklist;
    
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
     * @return state_orders
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
     * @return state_orders
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
     * @return state_orders
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
     * Set node
     *
     * @param string $node
     * @return state_orders
     */
    public function setNode($node)
    {
        $this->node = $node;
    
        return $this;
    }

    /**
     * Get node
     *
     * @return string 
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Set job_chain
     *
     * @param string $jobChain
     * @return state_orders
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
     * Set state
     *
     * @param string $state
     * @return state_orders
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
     * Set order_id
     *
     * @param integer $orderId
     * @return state_orders
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;
    
        return $this;
    }

    /**
     * Get order_id
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return state_orders
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
     * Set next_start_time
     *
     * @param \DateTime $nextStartTime
     * @return state_orders
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
     * Set initial_state
     *
     * @param string $initialState
     * @return state_orders
     */
    public function setInitialState($initialState)
    {
        $this->initial_state = $initialState;
    
        return $this;
    }

    /**
     * Get initial_state
     *
     * @return string 
     */
    public function getInitialState()
    {
        return $this->initial_state;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return state_orders
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    
        return $this;
    }

    /**
     * Get priority
     *
     * @return integer 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return state_orders
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set start_time
     *
     * @param \DateTime $startTime
     * @return state_orders
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;
    
        return $this;
    }

    /**
     * Get start_time
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set suspended
     *
     * @param integer $suspended
     * @return state_orders
     */
    public function setSuspended($suspended)
    {
        $this->suspended = $suspended;
    
        return $this;
    }

    /**
     * Get suspended
     *
     * @return integer 
     */
    public function getSuspended()
    {
        return $this->suspended;
    }

    /**
     * Set histoty_id
     *
     * @param integer $histotyId
     * @return state_orders
     */
    public function setHistotyId($histotyId)
    {
        $this->histoty_id = $histotyId;
    
        return $this;
    }

    /**
     * Get histoty_id
     *
     * @return integer 
     */
    public function getHistotyId()
    {
        return $this->histoty_id;
    }

    /**
     * Set task
     *
     * @param string $task
     * @return state_orders
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
     * Set in_process_since
     *
     * @param string $inProcessSince
     * @return state_orders
     */
    public function setInProcessSince($inProcessSince)
    {
        $this->in_process_since = $inProcessSince;
    
        return $this;
    }

    /**
     * Get in_process_since
     *
     * @return string 
     */
    public function getInProcessSince()
    {
        return $this->in_process_since;
    }

    /**
     * Set touched
     *
     * @param string $touched
     * @return state_orders
     */
    public function setTouched($touched)
    {
        $this->touched = $touched;
    
        return $this;
    }

    /**
     * Get touched
     *
     * @return string 
     */
    public function getTouched()
    {
        return $this->touched;
    }

    /**
     * Set last_write_time
     *
     * @param \DateTime $lastWriteTime
     * @return state_orders
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
     * Set history_id
     *
     * @param integer $historyId
     * @return state_orders
     */
    public function setHistoryId($historyId)
    {
        $this->history_id = $historyId;
    
        return $this;
    }

    /**
     * Get history_id
     *
     * @return integer 
     */
    public function getHistoryId()
    {
        return $this->history_id;
    }

    /**
     * Set end_state
     *
     * @param string $endState
     * @return state_orders
     */
    public function setEndState($endState)
    {
        $this->end_state = $endState;
    
        return $this;
    }

    /**
     * Get end_state
     *
     * @return string 
     */
    public function getEndState()
    {
        return $this->end_state;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return Orders
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
     * @param string $order
     * @return Orders
     */
    public function setOrder($order)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return string 
     */
    public function getOrder()
    {
        return $this->order;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->node = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add job_chain_node
     *
     * @param \Arii\FocusBundle\Entity\JobChainNodes $jobChainNode
     * @return Orders
     */
    public function addJobChainNode(\Arii\FocusBundle\Entity\JobChainNodes $jobChainNode)
    {
        $this->job_chain_node[] = $jobChainNode;
    
        return $this;
    }

    /**
     * Remove job_chain_node
     *
     * @param \Arii\FocusBundle\Entity\JobChainNodes $jobChainNode
     */
    public function removeJobChainNode(\Arii\FocusBundle\Entity\JobChainNodes $jobChainNode)
    {
        $this->job_chain_node->removeElement($jobChainNode);
    }

    /**
     * Get job_chain_node
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getJobChainNode()
    {
        return $this->job_chain_node;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Orders
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
     * Set job_chain_node
     *
     * @param \Arii\FocusBundle\Entity\JobChainNodes $jobChainNode
     * @return Orders
     */
    public function setJobChainNode(\Arii\FocusBundle\Entity\JobChainNodes $jobChainNode = null)
    {
        $this->job_chain_node = $jobChainNode;
    
        return $this;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Orders
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