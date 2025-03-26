<?php

namespace App\Controller;

use App\Entity\Candidat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatController extends AbstractController
{
    private $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    #[Route('/candidat/add', name: 'candidat_add')]
    public function addCommande(EntityManagerInterface $em): Response
    {
        $candidat = new Candidat();
        $candidat->setNom('Doe');
        $candidat->setPrenom('John');
        $candidat->setEmail('john.doe@example.com');

        $em->persist($candidat);
        $em->flush();

        // Rediriger vers la timeline de la commande après création
        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/{id}', name: 'candidature_timeline')]
    public function showTimeline(Candidat $candidat): Response
    {
        $workflow = $this->workflowRegistry->get($candidat, 'candidature_workflow');

        return $this->render('candidature/candidature_timeline.html.twig', [
            'candidat' => $candidat,
            'workflow' => $workflow,
        ]);
    }

    #[Route('/candidature/progress/{id}/{transition}', name: 'candidature_progress')]
    public function progressCandidature(Candidat $candidat, string $transition, EntityManagerInterface $em): Response
    {
        $workflow = $this->workflowRegistry->get($candidat, 'candidature_workflow');

        if ($workflow->can($candidat, $transition)) {
            $workflow->apply($candidat, $transition);
            $em->flush();
        }

        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }
}

