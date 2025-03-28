<?php

namespace App\Controller;

use Faker\Factory;
use App\Entity\Candidat;
use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatController extends AbstractController
{
    private $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    #[Route('/candidature', name: 'candidatures')]
    public function candidatures(Request $request, CandidatRepository $cr): Response
    {
        $statusFilter = $request->query->get('status');
        
        if ($statusFilter && $statusFilter != 'all') {
            $candidats = $cr->findBy(['status' => $statusFilter]);
        } else {
            $candidats = $cr->findBy([], ['dateCandidature' => 'DESC']);
        }
        
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
            // $candidat->setEmail($faker->unique()->safeEmail);
            $candidat->setEmail(mb_strtolower(
                transliterator_transliterate('Any-Latin; Latin-ASCII', str_replace(" ","",$candidat->getPrenom())) . "." .
                transliterator_transliterate('Any-Latin; Latin-ASCII', str_replace(" ","",$candidat->getNom())) . "@exemple.com"
            ));
            
            $em->persist($candidat);
        }

        $em->flush();

        return new Response("{$count} candidats ajoutés !");
    }

    #[Route('/calendar/events', name: 'calendar_events', methods: ['GET'])]
    public function getCalendarEvents(CandidatRepository $candidatRepository): JsonResponse
    {
        $candidats = $candidatRepository->findAll();
        $events = [];

        foreach ($candidats as $candidat) {
            if ($candidat->getDateEntretien()) {
                $events[] = [
                    'title' => "Entretien - " . $candidat->getPrenom() . " " . $candidat->getNom(),
                    'start' => $candidat->getDateEntretien()->format('Y-m-d H:i:s'),
                    'color' => '#007bff'
                ];
            }
            if ($candidat->getDateTest()) {
                $events[] = [
                    'title' => "Test - " . $candidat->getPrenom() . " " . $candidat->getNom(),
                    'start' => $candidat->getDateTest()->format('Y-m-d H:i:s'),
                    'color' => '#28a745'
                ];
            }
        }

        return new JsonResponse($events);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        return $this->render('candidature/calendar.html.twig', []);
    }

    #[Route('/candidature/{id}/update-entretien', name: 'candidature_update_entretien', methods: ['POST'])]
    public function updateEntretien(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $dateEntretien = $request->request->get('dateEntretien');
        
        if ($dateEntretien) {
            $candidat->setDateEntretien(new \DateTime($dateEntretien));
            $entityManager->flush();
            $this->addFlash('success', 'Date d\'entretien mise à jour.');
        }

        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/{id}/update-test', name: 'candidature_update_test', methods: ['POST'])]
    public function updateTest(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $dateTest = $request->request->get('dateTest');

        if ($dateTest) {
            $candidat->setDateTest(new \DateTime($dateTest));
            $entityManager->flush();
            $this->addFlash('success', 'Date du test technique mise à jour.');
        }

        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/{id}/update-validation-rh', name: 'candidature_update_validation_rh', methods: ['POST'])]
    public function updateValidationRH(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $remarqueRH = $request->request->get('remarqueRH');

        if ($remarqueRH) {
            $candidat->setRemarqueRH($remarqueRH);
            $entityManager->flush();
            $this->addFlash('success', 'Remarque RH enregistrée.');
        }

        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/{id}/update-offre', name: 'candidature_update_offre', methods: ['POST'])]
    public function updateOffre(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        $salairePropose = $request->request->get('salairePropose');

        if ($salairePropose) {
            $candidat->setSalairePropose((float) $salairePropose);
            $entityManager->flush();
            $this->addFlash('success', 'Offre mise à jour.');
        }

        return $this->redirectToRoute('candidature_timeline', ['id' => $candidat->getId()]);
    }

    #[Route('/candidature/{id}', name: 'candidature_timeline')]
    public function showTimeline(Candidat $candidat): Response
    {
        $workflow = $this->workflowRegistry->get($candidat, 'candidature_workflow');

        // Mapping des statuts avec couleurs et classes appropriées
        $statusMapping = [
            'candidature_recue' => [
                'badgeClass' => 'badge-primary',
                'bgColor' => '#007bff', // Couleur du fond
                'listClass' => 'list-group-item-primary'
            ],
            'entretien_planifie' => [
                'badgeClass' => 'badge-warning',
                'bgColor' => '#ffc107',
                'listClass' => 'list-group-item-warning'
            ],
            'entretien_realise' => [
                'badgeClass' => 'badge-info',
                'bgColor' => '#17a2b8',
                'listClass' => 'list-group-item-info'
            ],
            'offre_envoyee' => [
                'badgeClass' => 'badge-success',
                'bgColor' => '#28a745',
                'listClass' => 'list-group-item-success'
            ],
            'accepte' => [
                'badgeClass' => 'badge-success',
                'bgColor' => '#28a745',
                'listClass' => 'list-group-item-success'
            ],
            'refuse' => [
                'badgeClass' => 'badge-danger',
                'bgColor' => '#f59f8d',
                'listClass' => 'list-group-item-danger'
            ]
        ];

        return $this->render('candidature/candidature_timeline.html.twig', [
            'candidat' => $candidat,
            'workflow' => $workflow,
            'statusMapping' => $statusMapping
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

