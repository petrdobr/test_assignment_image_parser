<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $number = 1;
        return $this->render('homepage.html.twig', [
            'number' => $number,
        ]);
    }
}