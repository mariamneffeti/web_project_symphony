<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private RouterInterface $router) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        return match(true) {
    in_array('ROLE_COMPANY', $roles)  => new RedirectResponse($this->router->generate('company_dashboard')),
    in_array('ROLE_EMPLOYEE', $roles) => new RedirectResponse($this->router->generate('employee_dashboard')),
    default                           => new RedirectResponse($this->router->generate('client_dashboard')),
};
    }
}