<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_job_chain_nodes
 *
 * @ORM\Table(name="FOCUS_JOB_CHAIN_NODES")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\JobChainNodesRepository")
 */
class JobChainNodes
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\JobChains")
     * @ORM\JoinColumn(nullable=true)
     */
    private $job_chain;
    
    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Jobs")
     * @ORM\JoinColumn(nullable=true)
     **/   
    private $job;

    // Pour les sous-chaines
    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\JobChains")
     * @ORM\JoinColumn(nullable=true)
     **/   
    private $chain;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100)
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer" )
     */
    private $ordering;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="next_state", type="string", length=100, nullable=true)
     */
    private $next_state;

    /**
     * @var string
     *
     * @ORM\Column(name="error_state", type="string", length=100, nullable=true)
     */
    private $error_state;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=32, nullable=true)
     */
    private $action;

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
     * @return state_job_chain_nodes
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
     * Set node
     *
     * @param string $node
     * @return state_job_chain_nodes
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
     * @return state_job_chain_nodes
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
     * Set job
     *
     * @param string $job
     * @return state_job_chain_nodes
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
     * Set state
     *
     * @param string $state
     * @return state_job_chain_nodes
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
     * @return state_job_chain_nodes
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
     * Set next_state
     *
     * @param string $nextState
     * @return state_job_chain_nodes
     */
    public function setNextState($nextState)
    {
        $this->next_state = $nextState;
    
        return $this;
    }

    /**
     * Get next_state
     *
     * @return string 
     */
    public function getNextState()
    {
        return $this->next_state;
    }

    /**
     * Set error_state
     *
     * @param string $errorState
     * @return state_job_chain_nodes
     */
    public function setErrorState($errorState)
    {
        $this->error_state = $errorState;
    
        return $this;
    }

    /**
     * Get error_state
     *
     * @return string 
     */
    public function getErrorState()
    {
        return $this->error_state;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return state_job_chain_nodes
     */
    public function setAction($action)
    {
        $this->action = $action;
    
        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set job_chain_id
     *
     * @param integer $jobChainId
     * @return state_job_chain_nodes
     */
    public function setJobChainId($jobChainId)
    {
        $this->job_chain_id = $jobChainId;
    
        return $this;
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
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return JobChainNodes
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
     * @return JobChainNodes
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