<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SchedulerOrderStepHistory
 *
 * @ORM\Table(name="FOCUS_ORDER_STEP_HISTORY")
 * @ORM\Entity(repositoryClass="Arii\CoreBundle\Entity\SchedulerOrderStepHistoryRepository")
 */
class SchedulerOrderStepHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\SchedulerOrderHistory")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $ORDER;

    /**
     * @var integer
     *
     * @ORM\Column(name="SOURCE", type="integer")
     */
    private $SOURCE;

    /**
     * @var integer
     *
     * @ORM\Column(name="STEP", type="integer")
     */
    private $STEP;

    /**
     * @var string
     *
     * @ORM\Column(name="STATE", type="string", length=100)
     */
    private $STATE;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="START_TIME", type="datetime")
     */
    private $START_TIME;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="END_TIME", type="datetime")
     */
    private $END_TIME;

    /**
     * @var integer
     *
     * @ORM\Column(name="ERROR", type="integer")
     */
    private $ERROR;

    /**
     * @var string
     *
     * @ORM\Column(name="ERROR_CODE", type="string", length=50)
     */
    private $ERROR_CODE;

    /**
     * @var string
     *
     * @ORM\Column(name="ERROR_TEXT", type="string", length=250)
     */
    private $ERROR_TEXT;

    /**
     * @var string
     *
     * @ORM\Column(name="SPOOLER", type="string", length=100)
     */
    private $SPOOLER;

    /**
     * @var string
     *
     * @ORM\Column(name="CLUSTER_MEMBER", type="string", length=100)
     */
    private $CLUSTER_MEMBER;

    /**
     * @var string
     *
     * @ORM\Column(name="JOB_NAME", type="string", length=255)
     */
    private $JOB_NAME;

    /**
     * @var string
     *
     * @ORM\Column(name="CAUSE", type="string", length=50)
     */
    private $CAUSE;

    /**
     * @var integer
     *
     * @ORM\Column(name="STEPS", type="integer")
     */
    private $STEPS;

    /**
     * @var integer
     *
     * @ORM\Column(name="EXIT_CODE", type="integer")
     */
    private $EXIT_CODE;

    /**
     * @var string
     *
     * @ORM\Column(name="PARAMETERS", type="text")
     */
    private $PARAMETERS;

    /**
     * @var string
     *
     * @ORM\Column(name="ITEM_START", type="string", length=250)
     */
    private $ITEM_START;

    /**
     * @var string
     *
     * @ORM\Column(name="ITEM_STOP", type="string", length=250)
     */
    private $ITEM_STOP;

    /**
     * @var integer
     *
     * @ORM\Column(name="PID", type="integer")
     */
    private $PID;

     /**
     * @var float
     *
     * @ORM\Column(name="AVG_RUNTIME", type="float")
     */
    private $AVG_RUNTIME;

     /**
     * @var float
     *
     * @ORM\Column(name="MIN_RUNTIME", type="float")
     */
    private $MIN_RUNTIME;

     /**
     * @var float
     *
     * @ORM\Column(name="MAX_RUNTIME", type="float")
     */
    private $MAX_RUNTIME;

    /**
     * @var integer
     *
     * @ORM\Column(name="RUN_COUNT", type="integer")
     */
    private $RUN_COUNT;

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
     * Set HISTORY_ID
     *
     * @param integer $hISTORYID
     * @return Scheduler_order_step_history
     */
    public function setHISTORYID($hISTORYID)
    {
        $this->HISTORY_ID = $hISTORYID;
    
        return $this;
    }

    /**
     * Get HISTORY_ID
     *
     * @return integer 
     */
    public function getHISTORYID()
    {
        return $this->HISTORY_ID;
    }

    /**
     * Set STEP
     *
     * @param integer $sTEP
     * @return Scheduler_order_step_history
     */
    public function setSTEP($sTEP)
    {
        $this->STEP = $sTEP;
    
        return $this;
    }

    /**
     * Get STEP
     *
     * @return integer 
     */
    public function getSTEP()
    {
        return $this->STEP;
    }

    /**
     * Set TASK_ID
     *
     * @param integer $tASKID
     * @return Scheduler_order_step_history
     */
    public function setTASKID($tASKID)
    {
        $this->TASK_ID = $tASKID;
    
        return $this;
    }

    /**
     * Get TASK_ID
     *
     * @return integer 
     */
    public function getTASKID()
    {
        return $this->TASK_ID;
    }

    /**
     * Set STATE
     *
     * @param string $sTATE
     * @return Scheduler_order_step_history
     */
    public function setSTATE($sTATE)
    {
        $this->STATE = $sTATE;
    
        return $this;
    }

    /**
     * Get STATE
     *
     * @return string 
     */
    public function getSTATE()
    {
        return $this->STATE;
    }

    /**
     * Set START_TIME
     *
     * @param \DateTime $sTARTTIME
     * @return Scheduler_order_step_history
     */
    public function setSTARTTIME($sTARTTIME)
    {
        $this->START_TIME = $sTARTTIME;
    
        return $this;
    }

    /**
     * Get START_TIME
     *
     * @return \DateTime 
     */
    public function getSTARTTIME()
    {
        return $this->START_TIME;
    }

    /**
     * Set END_TIME
     *
     * @param \DateTime $eNDTIME
     * @return Scheduler_order_step_history
     */
    public function setENDTIME($eNDTIME)
    {
        $this->END_TIME = $eNDTIME;
    
        return $this;
    }

    /**
     * Get END_TIME
     *
     * @return \DateTime 
     */
    public function getENDTIME()
    {
        return $this->END_TIME;
    }

    /**
     * Set ERROR
     *
     * @param integer $eRROR
     * @return Scheduler_order_step_history
     */
    public function setERROR($eRROR)
    {
        $this->ERROR = $eRROR;
    
        return $this;
    }

    /**
     * Get ERROR
     *
     * @return integer 
     */
    public function getERROR()
    {
        return $this->ERROR;
    }

    /**
     * Set ERROR_CODE
     *
     * @param string $eRRORCODE
     * @return Scheduler_order_step_history
     */
    public function setERRORCODE($eRRORCODE)
    {
        $this->ERROR_CODE = $eRRORCODE;
    
        return $this;
    }

    /**
     * Get ERROR_CODE
     *
     * @return string 
     */
    public function getERRORCODE()
    {
        return $this->ERROR_CODE;
    }

    /**
     * Set ERROR_TEXT
     *
     * @param string $eRRORTEXT
     * @return Scheduler_order_step_history
     */
    public function setERRORTEXT($eRRORTEXT)
    {
        $this->ERROR_TEXT = $eRRORTEXT;
    
        return $this;
    }

    /**
     * Get ERROR_TEXT
     *
     * @return string 
     */
    public function getERRORTEXT()
    {
        return $this->ERROR_TEXT;
    }
}
