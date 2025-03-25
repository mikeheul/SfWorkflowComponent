<?php

// src/Controller/CommandeController.php
namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    private $workflowRegistry;

    // Le constructeur pour injecter le service Registry
    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    #[Route('/commande/add', name: 'commande_add')]
    public function addCommande(EntityManagerInterface $em): Response
    {
        $commande = new Commande();
        $em->persist($commande);
        $em->flush();

        return new Response('Commande ajoutée. Statut : ' . $commande->getStatus());
    }

    // Route pour démarrer une commande
    #[Route('/commande/start/{id}', name: 'commande_start')]
    public function startCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        // Récupérer le workflow pour cette commande
        $workflow = $this->workflowRegistry->get($commande);

        // Vérifier si la transition 'start' est possible
        if ($workflow->can($commande, 'start')) {
            // Appliquer la transition 'start' pour faire passer la commande à l'état 'en_cours'
            $workflow->apply($commande, 'start');
            // Sauvegarder l'entité mise à jour
            $em->flush();
        }

        return new Response('Commande démarrée. Nouveau statut : ' . $commande->getStatus());
    }

    // Route pour livrer une commande
    #[Route('/commande/deliver/{id}', name: 'commande_deliver')]
    public function deliverCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande);

        if ($workflow->can($commande, 'deliver')) {
            // Appliquer la transition 'deliver' pour faire passer la commande à l'état 'livree'
            $workflow->apply($commande, 'deliver');
            $em->flush();
        }

        return new Response('Commande livrée. Nouveau statut : ' . $commande->getStatus());
    }

    // Route pour réinitialiser une commande
    #[Route('/commande/reset/{id}', name: 'commande_reset')]
    public function resetCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande);

        if ($workflow->can($commande, 'reset')) {
            // Appliquer la transition 'reset' pour remettre la commande à l'état 'en_attente'
            $workflow->apply($commande, 'reset');
            $em->flush();
        }

        return new Response('Commande réinitialisée. Nouveau statut : ' . $commande->getStatus());
    }
}
