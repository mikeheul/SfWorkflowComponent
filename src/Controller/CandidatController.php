<?php

namespace App\Controller;

use Faker\Factory;
use App\Entity\Candidat;
use App\Repository\CandidatRepository;
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

    #[Route('/candidature', name: 'candidatures')]
    public function candidatures(CandidatRepository $cr): Response
    {
        $candidats = $cr->findBy([], ['dateCandidature' => 'DESC']);

        return $this->render('candidature/candidatures.html.twig', [
            'candidats' => $candidats,
        ]);
    }

    #[Route('/candidature/add', name: 'candidat_add')]
    public function addCandidature(EntityManagerInterface $em): Response
    {
        $faker = Factory::create('fr_FR');

        $candidat = new Candidat();
        $candidat->setNom($faker->lastName);
        $candidat->setPrenom($faker->firstName);
        $candidat->setEmail($faker->unique()->safeEmail);

        $em->persist($candidat);
        $em->flush();

        // Rediriger vers la timeline de la commande après création
        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/add-multiple/{count}', name: 'candidat_add_multiple')]
    public function addMultipleCandidats(EntityManagerInterface $em, int $count = 5): Response
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < $count; $i++) {
            $candidat = new Candidat();
            $candidat->setNom($faker->lastName);
            $candidat->setPrenom($faker->firstName);
            $candidat->setEmail($faker->unique()->safeEmail);

            $em->persist($candidat);
        }

        $em->flush();

        return new Response("{$count} candidats ajoutés !");
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

