<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

Class VersioningService 
{

    

    public function __construct(private RequestStack $requestStack)
    {

    }

    

}

