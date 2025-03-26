<?php

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

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    // Route pour ajouter une commande
    #[Route('/commande/add', name: 'commande_add')]
    public function addCommande(EntityManagerInterface $em): Response
    {
        $commande = new Commande();
        $em->persist($commande);
        $em->flush();

        // Rediriger vers la timeline de la commande après création
        return $this->redirectToRoute('commande_timeline', ['id' => $commande->getId()]);
    }

    // Route pour démarrer une commande
    #[Route('/commande/start/{id}', name: 'commande_start')]
    public function startCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande, 'commande_workflow');

        if ($workflow->can($commande, 'start')) {
            $workflow->apply($commande, 'start');
            $em->flush();
        }

        // Rediriger vers la timeline après application de la transition
        return $this->redirectToRoute('commande_timeline', ['id' => $commande->getId()]);
    }

    // Route pour traiter une commande
    #[Route('/commande/process/{id}', name: 'commande_process')]
    public function processCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande, 'commande_workflow');

        if ($workflow->can($commande, 'process')) {
            $workflow->apply($commande, 'process');
            $em->flush();
        }

        // Rediriger vers la timeline après application de la transition
        return $this->redirectToRoute('commande_timeline', ['id' => $commande->getId()]);
    }

    // Route pour livrer une commande
    #[Route('/commande/deliver/{id}', name: 'commande_deliver')]
    public function deliverCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande, 'commande_workflow');

        if ($workflow->can($commande, 'deliver')) {
            $workflow->apply($commande, 'deliver');
            $em->flush();
        }

        // Rediriger vers la timeline après application de la transition
        return $this->redirectToRoute('commande_timeline', ['id' => $commande->getId()]);
    }

    // Route pour réinitialiser une commande
    #[Route('/commande/reset/{id}', name: 'commande_reset')]
    public function resetCommande(Commande $commande, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($commande, 'commande_workflow');

        if ($workflow->can($commande, 'reset')) {
            $workflow->apply($commande, 'reset');
            $em->flush();
        }

        // Rediriger vers la timeline après application de la transition
        return $this->redirectToRoute('commande_timeline', ['id' => $commande->getId()]);
    }

    // Afficher la timeline de la commande
    #[Route('/commande/timeline/{id}', name: 'commande_timeline')]
    public function showTimeline(Commande $commande): Response
    {
        return $this->render('commande/commande_timeline.html.twig', [
            'commande' => $commande,
        ]);
    }
}