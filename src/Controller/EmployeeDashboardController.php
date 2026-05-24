<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\MeetingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmployeeDashboardController extends AbstractController
{
    #[Route('/employee/dashboard', name: 'employee_dashboard')]
    public function dashboard(
        EmployeeRepository $employeeRepository,
        MeetingRepository  $meetingRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Find the Employee record linked to the logged-in user
        $employee = $employeeRepository->findOneBy(['userId' => $user->getId()]);

        if (!$employee) {
            // User has employee role but no employee record — send back to login
            $this->addFlash('error', 'No employee profile found for your account.');
            return $this->redirectToRoute('app_login');
        }

        // Upcoming meetings for this employee
        $meetings = $meetingRepository->findUpcomingForEmployee($employee->getId());

        return $this->render('client/index.html.twig', [
            'user'     => $user,
            'employee' => $employee,
            'meetings' => $meetings,
        ]);
    }

    #[Route('/employee/profile', name: 'employee_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('employee/profile.html.twig', [
            'user' => $user,
        ]);
    }
}