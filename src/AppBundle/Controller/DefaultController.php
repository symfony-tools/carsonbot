<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController
{
    /**
     * @Route("/")
     */
    public function homepageAction()
    {
        return new Response('<img src="https://pbs.twimg.com/media/B6dVMHzCYAASEhm.jpg" />');
    }
}
