<?php
// src/Arii/UserBundle/Entity/User.php

namespace Arii\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ARII_USER")
*/
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
    * @var string $last_name
    *
    * @ORM\Column(name="last_name", type="string", length=255)
    */
    private $last_name;

    /**
     ** @var string $first_name
    *
    * @ORM\Column(name="first_name", type="string", length=255)
    */
    private $first_name;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\CoreBundle\Entity\Team")
     * @ORM\JoinColumn(nullable=true)
     *      
     */
    private $team;

    /**
     * @ORM\ManyToOne(targetEntity="Arii\CoreBundle\Entity\Enterprise")
     * @ORM\JoinColumn(nullable=true)
     *      
     */
    private $enterprise;
    
    /**
     * Set enterprise
     *
     * @param \Arii\CoreBundle\Entity\Enterprise $enterprise
     * @return User
     */
    public function setEnterprise(\Arii\CoreBundle\Entity\Enterprise $enterprise = null)
    {
        $this->enterprise = $enterprise;
    
        return $this;
    }

    /**
     * Get enterprise
     *
     * @return \Arii\CoreBundle\Entity\Enterprise 
     */
    public function getEnterprise()
    {
        return $this->enterprise;
    }
    /**
     * Set first_name
     *
     * @param string $first_name
     * @return User
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    
        return $this;
    }

    /**
     * Get first_name
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $last_name
     * @return User
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    
        return $this;
    }

    /**
     * Get last_name
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->last_name;
    }

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
     * Set team
     *
     * @param \Arii\CoreBundle\Entity\Team $team
     * @return User
     */
    public function setTeam(\Arii\CoreBundle\Entity\Team $team = null)
    {
        $this->team = $team;
    
        return $this;
    }

    /**
     * Get team
     *
     * @return \Arii\CoreBundle\Entity\Team 
     */
    public function getTeam()
    {
        return $this->team;
    }
}