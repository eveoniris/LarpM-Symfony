<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        // Cache $cache,
    ) {
    }
}
