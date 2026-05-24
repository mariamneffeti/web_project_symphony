<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;
use App\Entity\Company;

abstract class AbstractCompanyController extends AbstractController
{
    /**
     * Gets the securely authenticated user from session memory.
     */
    protected function getCompanyUser(): User
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw new AccessDeniedException('You must be logged in to access this section.');
        }

        return $user;
    }

    /**
     * Gets the current user's assigned Company context.
     */
    protected function getCompanyContext(): Company
    {
        $company = $this->getCompanyUser()->getCompany();

        if (!$company) {
            throw new AccessDeniedException('Your user account is not associated with an active company profile.');
        }

        return $company;
    }
}