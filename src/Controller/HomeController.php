<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/acceuil.html.twig');
    }

    #[Route('/signin', name: 'app_signin')]
    public function signin(): Response
    {
        return $this->render('home/signin.html.twig');
    }

    #[Route('/signup', name: 'app_signup')]
    public function signup(): Response
    {
        return $this->render('home/signup.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }

    #[Route('/trajets', name: 'app_trajets')]
    public function trajets(): Response
    {
        return $this->render('pages/covoiturage/trajets.html.twig');
    }

    #[Route('/avis', name: 'app_avis')]
    public function avis(): Response
    {
        return $this->render('pages/avis.html.twig');
    }

    #[Route('/utilisateurs', name: 'app_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('pages/utilisateurs/utilisateurs.html.twig');
    }

    #[Route('/employers', name: 'app_employers')]
    public function employers(): Response
    {
        return $this->render('pages/employers/employers.html.twig');
    }

    #[Route('/legale', name: 'app_legale')]
    public function legale(): Response
    {
        return $this->render('pages/legale.html.twig');
    }

    // Routes d'administration dans le même contrôleur
    #[Route('/administrateur', name: 'app_administrateur')]
    public function administrateur(): Response
    {
        return $this->render('admin/administrateur.html.twig');
    }

    #[Route('/signup2', name: 'app_signup2')]
    public function signup2(): Response
    {
        return $this->render('admin/signup2.html.twig');
    }

    #[Route('/suspendu', name: 'app_suspendu')]
    public function suspendu(): Response
    {
        return $this->render('admin/suspendu.html.twig');
    }

    #[Route('/abonees', name: 'app_abonees')]
    public function abonees(): Response
    {
        return $this->render('admin/abonees.html.twig');
    }

    // Route 404
    #[Route('/404', name: 'app_404')]
    public function notFound(): Response
    {
        return $this->render('404.html.twig');
    }
}