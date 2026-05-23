<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

            $builder
                ->add('firstName', TextType::class)
                ->add('lastName', TextType::class)
                ->add('email', EmailType::class)
                ->add('role', ChoiceType::class, [
                    'choices' => ['Visitor' => 'visitor', 'Company' => 'company'],
                    'placeholder' => 'Select a role'
                ])
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'mapped' => false,
                    'first_options'  => ['label' => 'Password'],
                    'second_options' => ['label' => 'Confirm Password'],
                ])
                ->add('companyName', TextType::class, ['mapped' => false, 'required' => false])
                ->add('industry',    TextType::class, ['mapped' => false, 'required' => false])
                ->add('address',     TextType::class, ['mapped' => false, 'required' => false])
                ->add('phone',       TextType::class, ['mapped' => false, 'required' => false]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
