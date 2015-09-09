<?php

namespace Arii\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Arii\UserBundle\DependencyInjection\Security\Factory\AriiFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AriiUserBundle extends Bundle
{
    
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
